<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = md5($data['password'] ?? '');

$sql = "SELECT username, is_admin FROM users WHERE username=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  echo json_encode([
    "status" => "success",
    "username" => $row['username'],
    "is_admin" => $row['is_admin']
  ]);
} else {
  echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
}
?>
