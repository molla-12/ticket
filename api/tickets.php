<?php
header("Content-Type: application/json");
include "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
  // Decode request body
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

  // 1️⃣ Validate that origin and destination are not the same
  if ($origin === $destination) {
    echo json_encode(["status" => "error", "message" => "Origin and destination cannot be the same."]);
    exit;
  }

  // 2️⃣ Check if the seat is already taken for this trip (same origin, destination, and date)
  $check = $conn->prepare("
    SELECT id FROM tickets 
    WHERE seat = ? AND origin = ? AND destination = ? AND travel_date = ?
  ");
  $check->bind_param("isss", $seat, $origin, $destination, $date);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Seat number already booked for this route and date."]);
    exit;
  }

  // 3️⃣ Insert new record
  $stmt = $conn->prepare("
    INSERT INTO tickets (seat, name, phone, origin, destination, travel_date, amount, ticket_number, comment)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("isssssiss", $seat, $name, $phone, $origin, $destination, $date, $amount, $ticketNumber, $comment);

  if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Ticket booked successfully."]);
  } else {
    echo json_encode(["status" => "error", "message" => "Database insert failed: " . $conn->error]);
  }

} elseif ($method === "GET") {
  // Get all tickets (optional filter)
  $destination = $_GET['destination'] ?? '';
  $sql = "SELECT * FROM tickets";
  if ($destination) {
    $sql .= " WHERE destination = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $destination);
  } else {
    $stmt = $conn->prepare($sql);
  }

  $stmt->execute();
  $result = $stmt->get_result();
  $tickets = [];
  while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
  }

  echo json_encode($tickets);
}
?>
