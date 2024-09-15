<?php
$servername = "servername";
$username = "username";
$password = "password";
$dbname = "dbname";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection error: " . $conn->connect_error);
}
?>
