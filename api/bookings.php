<?php
// api/bookings.php - Booking creation and retrieval
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

class Booking {
    private $conn;
    private $table = "bookings";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        if (!$this->conn) { return false; }

        $query = "INSERT INTO {$this->table} 
            (user_id, room_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, num_guests, total_price, status, special_requests)
            VALUES (:user_id, :room_id, :guest_name, :guest_email, :guest_phone, :check_in_date, :check_out_date, :num_guests, :total_price, :status, :special_requests)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':room_id', $data['room_id']);
            $stmt->bindParam(':guest_name', $data['guest_name']);
            $stmt->bindParam(':guest_email', $data['guest_email']);
            $stmt->bindParam(':guest_phone', $data['guest_phone']);
            $stmt->bindParam(':check_in_date', $data['check_in_date']);
            $stmt->bindParam(':check_out_date', $data['check_out_date']);
            $stmt->bindParam(':num_guests', $data['num_guests']);
            $stmt->bindParam(':total_price', $data['total_price']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':special_requests', $data['special_requests']);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getByUser($userId) {
        if (!$this->conn) { return []; }
        $query = "SELECT id, room_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, num_guests, total_price, status, special_requests, created_at
                  FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

$db = (new Database())->getConnection();
$booking = new Booking($db);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    if ($action === 'create') {
        $payload = [
            'user_id' => $data['user_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'guest_name' => $data['guest_name'] ?? '',
            'guest_email' => $data['guest_email'] ?? '',
            'guest_phone' => $data['guest_phone'] ?? '',
            'check_in_date' => $data['check_in_date'] ?? '',
            'check_out_date' => $data['check_out_date'] ?? '',
            'num_guests' => $data['num_guests'] ?? 1,
            'total_price' => $data['total_price'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'special_requests' => $data['special_requests'] ?? ''
        ];
        if ($booking->create($payload)) {
            echo json_encode(['success'=>true,'message'=>'Booking saved']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to save booking']);
        }
    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
    }
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'getByUser') {
        $userId = $_GET['userId'] ?? 0;
        $rows = $booking->getByUser($userId);
        echo json_encode(['success'=>true,'bookings'=>$rows]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unsupported method']);
