<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$oldPassword = md5($data['oldPassword'] ?? '');
$newPassword = md5($data['newPassword'] ?? '');

$sql = "SELECT id FROM users WHERE username=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $oldPassword);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(["status" => "error", "message" => "Old password incorrect."]);
  exit;
}

$update = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$update->bind_param("ss", $newPassword, $username);
$update->execute();

echo json_encode(["status" => "success", "message" => "Password changed successfully."]);
?>
