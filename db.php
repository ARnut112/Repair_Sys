<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "repair_system";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
