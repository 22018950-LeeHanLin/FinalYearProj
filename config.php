<?php
$conn = new mysqli("localhost", "root", "", "myproject");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
