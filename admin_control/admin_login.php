<?php
/**
 * Admin Login Backend - PHP
 * Handles admin authentication and database operations
 * 
 * Database Configuration:
 * - Create a database named 'bookeasy'
 * - Create table 'admins' with fields: id, email, password, name, created_at
 */

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up logging
define('LOG_FILE', __DIR__ . '/admin_login.log');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

logMessage('--- New Request ---');

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change to your database username
define('DB_PASS', '');              // Change to your database password
define('DB_NAME', 'bookeasy');      // Change to your database name

/**
 * Create database connection
 * @return mysqli Database connection object
 */
function getDBConnection() {
    logMessage('Connecting to: ' . DB_HOST . ' User: ' . DB_USER . ' DB: ' . DB_NAME);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        logMessage('DB Connection failed: ' . $conn->connect_error);
        respondWithError('Database connection failed: ' . $conn->connect_error);
    }
    
    logMessage('DB Connection successful');
    return $conn;
}

/**
 * Send JSON error response and exit
 * @param string $message Error message
 * @param int $code HTTP status code
 */
function respondWithError($message, $code = 500) {
    logMessage('Error Response (Code ' . $code . '): ' . $message);
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit();
}

/**
 * Send JSON success response
 * @param array $data Response data
 */
function respondWithSuccess($data) {
    http_response_code(200);
    echo json_encode(array_merge(['success' => true], $data));
    exit();
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool Validation result
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Authenticate admin user
 * @param string $email Admin email
 * @param string $password Admin password
 * @return array Authentication result
 */
function authenticateAdmin($email, $password) {
    $conn = getDBConnection();
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, email, password, name FROM admins WHERE email = ? AND active = 1");
    
    if (!$stmt) {
        logMessage('Database query preparation failed');
        $conn->close();
        respondWithError('Database query preparation failed');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    logMessage('Query rows found: ' . $result->num_rows);
    
    if ($result->num_rows === 0) {
        logMessage('No admin found with email: ' . $email);
        $stmt->close();
        $conn->close();
        return [
            'authenticated' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    logMessage('Admin found: ' . $admin['email'] . ', ID: ' . $admin['id']);
    logMessage('Checking password...');
    
    // Simple plain text password comparison (no hashing for offline project)
    if ($password === $admin['password']) {
        // Update last login timestamp
        $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $admin['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        logMessage('Login successful for: ' . $email);
        $conn->close();
        
        return [
            'authenticated' => true,
            'adminId' => $admin['id'],
            'adminName' => $admin['name'],
            'adminEmail' => $admin['email']
        ];
    } else {
        logMessage('Password verification failed for: ' . $email);
        $conn->close();
        return [
            'authenticated' => false,
            'message' => 'Invalid email or password'
        ];
    }
}

/**
 * Create new admin account (for setup purposes)
 * @param string $email Admin email
 * @param string $password Admin password
 * @param string $name Admin name
 * @return bool Success status
 */
function createAdmin($email, $password, $name) {
    $conn = getDBConnection();
    
    // Check if admin already exists
    $checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        logMessage('Admin already exists: ' . $email);
        $checkStmt->close();
        $conn->close();
        return false;
    }
    $checkStmt->close();
    
    // Store plain text password (no hashing for offline project)
    $stmt = $conn->prepare("INSERT INTO admins (email, password, name, created_at, active) VALUES (?, ?, ?, NOW(), 1)");
    $stmt->bind_param("sss", $email, $password, $name);
    
    $success = $stmt->execute();
    logMessage('Admin created: ' . $email . ', Success: ' . ($success ? 'true' : 'false'));
    $stmt->close();
    $conn->close();
    
    return $success;
}

// Main request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        respondWithError('Invalid JSON data', 400);
    }
    
    $action = isset($data['action']) ? $data['action'] : 'login';
    
    switch ($action) {
        case 'login':
            // Validate input
            if (!isset($data['email']) || !isset($data['password'])) {
                logMessage('Missing email or password in request');
                respondWithError('Email and password are required', 400);
            }
            
            $email = trim($data['email']);
            $password = $data['password'];
            logMessage('Login attempt for email: ' . $email);
            
            if (!validateEmail($email)) {
                respondWithError('Invalid email format', 400);
            }
            
            if (strlen($password) < 6) {
                respondWithError('Password must be at least 6 characters', 400);
            }
            
            // Authenticate admin
            $authResult = authenticateAdmin($email, $password);
            
            if ($authResult['authenticated']) {
                respondWithSuccess([
                    'message' => 'Login successful',
                    'adminId' => $authResult['adminId'],
                    'adminName' => $authResult['adminName'],
                    'adminEmail' => $authResult['adminEmail']
                ]);
            } else {
                respondWithError($authResult['message'], 401);
            }
            break;
            
        case 'create':
            // Create new admin (use this for initial setup only)
            if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
                respondWithError('Email, password, and name are required', 400);
            }
            
            $email = trim($data['email']);
            $password = $data['password'];
            $name = trim($data['name']);
            
            if (!validateEmail($email)) {
                respondWithError('Invalid email format', 400);
            }
            
            $success = createAdmin($email, $password, $name);
            
            if ($success) {
                respondWithSuccess(['message' => 'Admin account created successfully']);
            } else {
                respondWithError('Failed to create admin account. Email may already exist.', 400);
            }
            break;
            
        default:
            respondWithError('Invalid action', 400);
    }
} else {
    respondWithError('Only POST requests are allowed', 405);
}


