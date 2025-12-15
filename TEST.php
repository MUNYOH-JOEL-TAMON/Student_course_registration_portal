<?php
require_once 'config.php';

echo "<h2>Connection Test</h2>";
echo "Connected to: " . $conn->host_info . "<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM courses");
$row = $result->fetch_assoc();
echo "Courses in database: " . $row['count'] . " (should be 17)";
?>