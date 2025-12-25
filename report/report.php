<?php
// report.php - Backend API for Report and Feedback Management
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
            error_log("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}

// Feedback class
class Feedback {
    private $conn;
    private $table_name = "feedback";

    public $lastError = '';

    public $id;
    public $type;
    public $email;
    public $details;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function isConnected() {
        return $this->conn !== null;
    }

    // Submit new feedback
    public function submitFeedback() {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  SET type=:type, email=:email, details=:details, status='pending'";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":type", $this->type);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":details", $this->details);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error submitting feedback: " . $e->getMessage());
            return false;
        }
    }

    // Get all feedback (Admin function)
    public function getAllFeedback($status = null) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return [];
        }

        $query = "SELECT * FROM " . $this->table_name;
        if ($status) {
            $query .= " WHERE status = :status";
        }
        $query .= " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            if ($status) {
                $stmt->bindParam(":status", $status);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error fetching feedback: " . $e->getMessage());
            return [];
        }
    }

    // Get feedback by ID
    public function getFeedbackById($id) {
        if (!$this->conn) {
            $this->lastError = 'Database connection failed.';
            return null;
        }

        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error fetching feedback: " . $e->getMessage());
            return null;
        }
    }

    // Update feedback status (Admin function)
    public function updateStatus($id, $status) {
        if (!$this->conn) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET status=:status WHERE id=:id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error updating feedback status: " . $e->getMessage());
            return false;
        }
    }

    // Delete feedback (Admin function)
    public function deleteFeedback($id) {
        if (!$this->conn) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error deleting feedback: " . $e->getMessage());
            return false;
        }
    }

    // Get feedback statistics (Admin function)
    public function getStatistics() {
        if (!$this->conn) {
            return null;
        }

        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN type = 'bug' THEN 1 ELSE 0 END) as bugs,
                    SUM(CASE WHEN type = 'feature' THEN 1 ELSE 0 END) as features,
                    SUM(CASE WHEN type = 'complaint' THEN 1 ELSE 0 END) as complaints
                  FROM " . $this->table_name;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return null;
        }
    }
}

// Handle requests
$database = new Database();
$db = $database->getConnection();
$feedback = new Feedback($db);

$method = $_SERVER['REQUEST_METHOD'];

// Handle POST requests
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = isset($data['action']) ? $data['action'] : '';

    switch ($action) {
        case 'submitFeedback':
            $feedback->type = $data['type'];
            $feedback->email = isset($data['email']) && $data['email'] ? $data['email'] : null;
            $feedback->details = $data['details'];

            if ($feedback->submitFeedback()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback submitted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to submit feedback',
                    'error' => $feedback->lastError
                ]);
            }
            break;

        case 'updateStatus':
            // Admin only - should check authentication
            $id = $data['id'];
            $status = $data['status'];
            
            if ($feedback->updateStatus($id, $status)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update status'
                ]);
            }
            break;

        case 'deleteFeedback':
            // Admin only - should check authentication
            $id = $data['id'];
            
            if ($feedback->deleteFeedback($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete feedback'
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
        case 'getAllFeedback':
            // Admin only - should check authentication
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $feedbackList = $feedback->getAllFeedback($status);
            echo json_encode([
                'success' => true,
                'feedback' => $feedbackList,
                'dbConnected' => $feedback->isConnected()
            ]);
            break;

        case 'getFeedbackById':
            // Admin only - should check authentication
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
            $feedbackDetails = $feedback->getFeedbackById($id);
            
            if ($feedbackDetails) {
                echo json_encode([
                    'success' => true,
                    'feedback' => $feedbackDetails
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Feedback not found'
                ]);
            }
            break;

        case 'getStatistics':
            // Admin only - should check authentication
            $stats = $feedback->getStatistics();
            
            if ($stats) {
                echo json_encode([
                    'success' => true,
                    'statistics' => $stats
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch statistics'
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
