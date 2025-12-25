<?php
// rooms.php - Backend API for Room Management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
class Database {
    private $host = "localhost";
    private $db_name = "bookeasy"; // updated to match phpMyAdmin db
    private $username = "root";
    private $password = ""; // set if your MySQL root user has a password
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

// Room class
class Room {
    private $conn;
    private $table_name = "rooms";

    public $id;
    public $name;
    public $description;
    public $price;
    public $image_url;
    public $amenities;
    public $is_available;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function isConnected() {
        return $this->conn !== null;
    }

    // Get all rooms
    public function getAllRooms() {
        if (!$this->conn) {
            return [];
        }

        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    // Search rooms
    public function searchRooms($searchTerm) {
        if (!$this->conn) {
            return [];
        }

        $query = "SELECT * FROM " . $this->table_name . " 
              WHERE (name LIKE :search OR description LIKE :search OR amenities LIKE :search)
              ORDER BY id ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $searchParam = "%{$searchTerm}%";
            $stmt->bindParam(":search", $searchParam);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    // Get room details by ID
    public function getRoomById($id) {
        if (!$this->conn) {
            return null;
        }

        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }

    // Add new room (Admin function)
    public function addRoom() {
        if (!$this->conn) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, description=:description, price=:price, 
                      image_url=:image_url, amenities=:amenities, is_available=:is_available";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":image_url", $this->image_url);
            $stmt->bindParam(":amenities", $this->amenities);
            $stmt->bindParam(":is_available", $this->is_available);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Update room (Admin function)
    public function updateRoom() {
        if (!$this->conn) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, description=:description, price=:price, 
                      image_url=:image_url, amenities=:amenities, is_available=:is_available
                  WHERE id=:id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":image_url", $this->image_url);
            $stmt->bindParam(":amenities", $this->amenities);
            $stmt->bindParam(":is_available", $this->is_available);
            $stmt->bindParam(":id", $this->id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Delete room (Admin function)
    public function deleteRoom($id) {
        if (!$this->conn) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET is_available = 0 WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}

// Handle requests
$database = new Database();
$db = $database->getConnection();
$room = new Room($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle GET requests
if ($method === 'GET') {
    switch ($action) {
        case 'getAllRooms':
            $rooms = $room->getAllRooms();
            echo json_encode([
                'success' => true,
                'rooms' => $rooms
            ]);
            break;

        case 'searchRooms':
            $query = isset($_GET['query']) ? $_GET['query'] : '';
            $rooms = $room->searchRooms($query);
            echo json_encode([
                'success' => true,
                'rooms' => $rooms
            ]);
            break;

        case 'getRoomDetails':
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
            $roomDetails = $room->getRoomById($id);
            if ($roomDetails) {
                echo json_encode([
                    'success' => true,
                    'room' => $roomDetails
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Room not found'
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

// Handle POST requests
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = isset($data['action']) ? $data['action'] : '';

    switch ($action) {
        case 'addRoom':
            // Admin only - should check authentication
            $room->name = $data['name'];
            $room->description = $data['description'];
            $room->price = $data['price'];
            $room->image_url = $data['image_url'];
            $room->amenities = $data['amenities'];
            $room->is_available = 1;

            if ($room->addRoom()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Room added successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add room',
                    'error' => $room->lastError ?: 'unknown error'
                ]);
            }
            break;

        case 'updateRoom':
            // Admin only - should check authentication
            $room->id = $data['id'];
            $room->name = $data['name'];
            $room->description = $data['description'];
            $room->price = $data['price'];
            $room->image_url = $data['image_url'];
            $room->amenities = $data['amenities'];
            $room->is_available = isset($data['is_available']) ? $data['is_available'] : 1;

            if ($room->updateRoom()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Room updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update room'
                ]);
            }
            break;

        case 'deleteRoom':
            // Admin only - should check authentication
            $id = $data['id'];
            if ($room->deleteRoom($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Room deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete room'
                ]);
            }
            break;

        case 'logBooking':
            // Log booking attempt - can be expanded to store in database
            $roomId = isset($data['roomId']) ? $data['roomId'] : 0;
            $roomName = isset($data['roomName']) ? $data['roomName'] : '';
            
            error_log("Booking attempt - Room ID: $roomId, Room Name: $roomName");
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking logged'
            ]);
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
