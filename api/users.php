<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

include "db.php";

// Turn off error display but log errors
error_reporting(0);

// Simple GET handler for users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Test database connection first
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        $sql = "SELECT id, username, is_admin, created_at FROM users ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        // Add debug information
        $response = [
            'users' => $users,
            'debug' => [
                'total_users' => $result ? $result->num_rows : 0,
                'query_success' => $result !== false,
                'database' => $conn->host_info,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'users' => [],
            'debug' => [
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    exit;
}

// DELETE handler (keep your existing DELETE code)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (!isset($data['id'])) {
            echo json_encode(["status" => "error", "message" => "User ID is required"]);
            exit;
        }
        
        $id = intval($data['id']);
        
        // Check if user exists
        $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $checkResult = $check->get_result();
        
        if ($checkResult->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "User not found"]);
            exit;
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Delete failed: " . $stmt->error]);
        }
        
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// If method not supported
echo json_encode(["status" => "error", "message" => "Method not allowed"]);
?>