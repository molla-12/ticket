<?php
header("Content-Type: application/json");
include "db.php";

// Enable error reporting for debugging (remove in production)
error_reporting(0); // Turn off error display to prevent HTML in JSON

try {
    $input = file_get_contents("php://input");
    if (empty($input)) {
        throw new Exception("No input data received");
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data");
    }

    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $is_admin = intval($data['is_admin'] ?? 0);

    // Validate input
    if (empty($username) || empty($password)) {
        throw new Exception("Username and password are required.");
    }

    if (strlen($username) < 3) {
        throw new Exception("Username must be at least 3 characters long.");
    }

    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$check) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        throw new Exception("Username already exists.");
    }

    // Hash password using MD5 (as used in your login)
    $hashed_password = md5($password);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $username, $hashed_password, $is_admin);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success", 
            "message" => "User registered successfully."
        ]);
    } else {
        throw new Exception("Registration failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}
?>