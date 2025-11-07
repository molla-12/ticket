<?php
$host = "localhost";
$user = "root"; // change if needed
$pass = "";
$dbname = "felege_bus";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die(json_encode(["status" => "error", "message" => "DB connection failed"]));
}
?>
