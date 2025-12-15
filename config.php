<?php
// config.php
$host = 'localhost';
$username = 'root'; // Change if you have different credentials
$password = ''; // Your MariaDB password
$database = 'student_portal';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>