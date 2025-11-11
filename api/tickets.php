<?php
header("Content-Type: application/json");
include "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
    // Create new ticket
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
    $created_by = trim($data['created_by']);

    // Validate that origin and destination are not the same
    if ($origin === $destination) {
        echo json_encode(["status" => "error", "message" => "Origin and destination cannot be the same."]);
        exit;
    }

    // Check if the seat is already taken for this trip
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

    // Insert new record
    $stmt = $conn->prepare("
        INSERT INTO tickets (seat, name, phone, origin, destination, travel_date, amount, ticket_number, comment, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssisss", $seat, $name, $phone, $origin, $destination, $date, $amount, $ticketNumber, $comment, $created_by);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Ticket booked successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database insert failed: " . $conn->error]);
    }

} elseif ($method === "GET") {
    // Get all tickets (optional filter)
    $destination = $_GET['destination'] ?? '';
    $date = $_GET['date'] ?? '';
    
    $sql = "SELECT * FROM tickets WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($destination) {
        $sql .= " AND destination = ?";
        $params[] = $destination;
        $types .= "s";
    }
    
    if ($date) {
        $sql .= " AND travel_date = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    $sql .= " ORDER BY travel_date DESC, seat ASC";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }

    echo json_encode($tickets);

} elseif ($method === "PUT") {
    // Update ticket
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id = intval($data['id']);
    $seat = intval($data['seat']);
    $name = trim($data['name']);
    $phone = trim($data['phone']);
    $origin = trim($data['origin']);
    $destination = trim($data['destination']);
    $date = trim($data['date']);
    $amount = floatval($data['amount']);
    $ticketNumber = trim($data['ticketNumber']);
    $comment = trim($data['comment']);

    // Check if seat is available (excluding current ticket)
    $check = $conn->prepare("
        SELECT id FROM tickets 
        WHERE seat = ? AND origin = ? AND destination = ? AND travel_date = ? AND id != ?
    ");
    $check->bind_param("isssi", $seat, $origin, $destination, $date, $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Seat number already booked for this route and date."]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE tickets 
        SET seat=?, name=?, phone=?, origin=?, destination=?, travel_date=?, amount=?, ticket_number=?, comment=?
        WHERE id=?
    ");
    $stmt->bind_param("isssssissi", $seat, $name, $phone, $origin, $destination, $date, $amount, $ticketNumber, $comment, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Ticket updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed: " . $conn->error]);
    }

} elseif ($method === "DELETE") {
    // Delete ticket
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);

    $stmt = $conn->prepare("DELETE FROM tickets WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Ticket deleted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Delete failed: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>