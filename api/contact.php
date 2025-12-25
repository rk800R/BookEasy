<?php
// api/contact.php - Contact message handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

class ContactMessage {
    private $conn;
    private $table = "contact_messages";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        if (!$this->conn) { return false; }

        $query = "INSERT INTO {$this->table} 
            (name, email, subject, message)
            VALUES (:name, :email, :subject, :message)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':subject', $data['subject']);
            $stmt->bindParam(':message', $data['message']);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}

$db = (new Database())->getConnection();
$contactMsg = new ContactMessage($db);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $payload = [
        'name' => $data['name'] ?? '',
        'email' => $data['email'] ?? '',
        'subject' => $data['subject'] ?? '',
        'message' => $data['message'] ?? ''
    ];
    
    if ($contactMsg->create($payload)) {
        echo json_encode(['success'=>true,'message'=>'Message sent successfully']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Failed to send message']);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid request method']);
