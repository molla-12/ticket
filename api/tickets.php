<?php
header("Content-Type: application/json");
include "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
  $data = json_decode(file_get_contents("php://input"), true);
  $seat = intval($data['seat']);
  $name = trim($data['name']);
  $phone = trim($data['phone']);
  $origin = trim($data['origin']);
  $destination = trim($data['destination']);
  $date = trim($data['date']);
  $amount = floatval($data['amount']);
  $ticketNumber = trim($data['ticketNumber']);
  $comment = trim($data['comment']);
  $createdBy = trim($data['created_by']);

  if ($origin === $destination) {
    echo json_encode(["status" => "error", "message" => "Origin and destination cannot be the same."]);
    exit;
  }

  $check = $conn->prepare("
    SELECT id FROM tickets 
    WHERE seat = ? AND origin = ? AND destination = ? AND travel_date = ?
  ");
  $check->bind_param("isss", $seat, $origin, $destination, $date);
  $check->execute();
  if ($check->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Seat already booked for this route/date."]);
    exit;
  }

  $stmt = $conn->prepare("
    INSERT INTO tickets (seat, name, phone, origin, destination, travel_date, amount, ticket_number, comment, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("isssssisss", $seat, $name, $phone, $origin, $destination, $date, $amount, $ticketNumber, $comment, $createdBy);
  $stmt->execute();

  echo json_encode(["status" => "success"]);

} elseif ($method === "GET") {
  $result = $conn->query("SELECT * FROM tickets ORDER BY id DESC");
  $tickets = $result->fetch_all(MYSQLI_ASSOC);
  echo json_encode($tickets);

} elseif ($method === "PUT") {
  $data = json_decode(file_get_contents("php://input"), true);
  $id = intval($data['id']);
  $name = trim($data['name']);
  $phone = trim($data['phone']);
  $amount = floatval($data['amount']);
  $comment = trim($data['comment']);

  $stmt = $conn->prepare("UPDATE tickets SET name=?, phone=?, amount=?, comment=? WHERE id=?");
  $stmt->bind_param("ssdsi", $name, $phone, $amount, $comment, $id);
  $stmt->execute();

  echo json_encode(["status" => "success"]);

} elseif ($method === "DELETE") {
  $id = intval($_GET['id'] ?? 0);
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
  }
  echo json_encode(["status" => "success"]);
}
?>
