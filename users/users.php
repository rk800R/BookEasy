<?php
// users.php - Backend API for User Authentication and Management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
class Database {
    private $host = "localhost";
    private $db_name = "bookeasy";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // Connection failed
        }

        return $this->conn;
    }
}

// User class
class User {
    private $conn;
    private $table_name = "users";

    public $lastError = '';

    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function isConnected() {
        return $this->conn !== null;
    }

    // User login (supports legacy plaintext and hashed passwords)
    public function login($email, $password) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return null;
        }

        $query = "SELECT id, name, email, phone, role, password FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            $stored = $row['password'] ?? '';
            $isValid = false;
            if ($stored) {
                // Try hashed verification first
                if (password_verify($password, $stored)) {
                    $isValid = true;
                } else if ($stored === $password) {
                    // Legacy plaintext match
                    $isValid = true;
                    // Rehash and upgrade on successful login
                    $this->updatePasswordHashed($row['id'], password_hash($password, PASSWORD_DEFAULT));
                }
            }

            if (!$isValid) {
                return null;
            }

            unset($row['password']);
            return $row;
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error during login: " . $e->getMessage());
            return null;
        }
    }

    // User registration
    public function register() {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return false;
        }

        // Check if email already exists
        $checkQuery = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        try {
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":email", $this->email);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                $this->lastError = 'Email already registered';
                return false;
            }
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, email=:email, password=:password, phone=:phone, role='user'";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            // Hash new passwords
            $hashed = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $hashed);
            $stmt->bindParam(":phone", $this->phone);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error registering user: " . $e->getMessage());
            return false;
        }
    }

    // Get user by ID
    public function getUserById($id) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return null;
        }

        $query = "SELECT id, name, email, phone, role, created_at FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }

    // Get user by email
    public function getUserByEmail($email) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return null;
        }

        $query = "SELECT id, name, email, phone, role, created_at FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }

    // Update user profile
    public function updateProfile($id) {
        if (!$this->conn) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, email=:email, phone=:phone
                  WHERE id=:id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":id", $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    // Update password helper (hashed value provided)
    private function updatePasswordHashed($id, $hashedPassword) {
        if (!$this->conn) {
            return false;
        }
        $query = "UPDATE " . $this->table_name . " SET password=:password WHERE id=:id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $hashedPassword);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }

    // Secure password change (verify current, then hash new)
    public function changePassword($id, $currentPassword, $newPassword) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return false;
        }

        // Fetch current password
        try {
            $stmt = $this->conn->prepare("SELECT password FROM " . $this->table_name . " WHERE id=:id LIMIT 1");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->lastError = 'User not found';
                return false;
            }
            $stored = $row['password'] ?? '';
            $valid = false;
            if (password_verify($currentPassword, $stored)) {
                $valid = true;
            } else if ($stored === $currentPassword) {
                // Legacy plaintext support
                $valid = true;
            }
            if (!$valid) {
                $this->lastError = 'Current password is incorrect';
                return false;
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                $this->lastError = 'New password must be at least 8 characters';
                return false;
            }

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            return $this->updatePasswordHashed($id, $hashed);
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }
}

// Handle requests
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

// Handle POST requests
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = isset($data['action']) ? $data['action'] : '';

    switch ($action) {
        case 'login':
            $email = $data['email'];
            $password = $data['password'];

            $userData = $user->login($email, $password);
            
            if ($userData) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $userData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password',
                    'error' => $user->lastError
                ]);
            }
            break;

        case 'register':
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->phone = isset($data['phone']) ? $data['phone'] : null;

            if ($user->register()) {
                // Get the newly created user
                $userData = $user->getUserByEmail($user->email);
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful',
                    'user' => $userData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $user->lastError ?: 'Failed to register user'
                ]);
            }
            break;

        case 'updateProfile':
            $userId = $data['userId'];
            $user->name = $data['name'];
            $user->phone = $data['phone'];

            if ($user->updateProfile($userId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ]);
            }
            break;

        case 'update':
            $userId = $data['id'];
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];

            if ($user->updateProfile($userId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile',
                    'error' => $user->lastError
                ]);
            }
            break;

        case 'updatePassword':
            $userId = $data['userId'] ?? null;
            $currentPassword = $data['currentPassword'] ?? '';
            $newPassword = $data['newPassword'] ?? '';
            $confirmPassword = $data['confirmPassword'] ?? '';

            if (!$userId || !$currentPassword || !$newPassword || !$confirmPassword) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields'
                ]);
                break;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode([
                    'success' => false,
                    'message' => 'New password and confirm password do not match'
                ]);
                break;
            }

            if ($user->changePassword($userId, $currentPassword, $newPassword)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $user->lastError ?: 'Failed to update password'
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
}

// Handle GET requests
if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'getUserById':
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
            $userData = $user->getUserById($id);
            
            if ($userData) {
                echo json_encode([
                    'success' => true,
                    'user' => $userData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
            break;

        case 'getUserByEmail':
            $email = isset($_GET['email']) ? $_GET['email'] : '';
            $userData = $user->getUserByEmail($email);
            
            if ($userData) {
                echo json_encode([
                    'success' => true,
                    'user' => $userData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
}
?>
