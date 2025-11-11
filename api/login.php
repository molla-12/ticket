<?php
session_start();
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = md5($data['password'] ?? '');

$sql = "SELECT * FROM users WHERE username=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        "status" => "success", 
        "username" => $user['username'],
        "is_admin" => $user['is_admin']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
}
?>