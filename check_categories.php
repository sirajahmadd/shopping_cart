<?php
include 'config.php';

echo "Current categories in database:\n";
$result = $conn->query("SELECT * FROM categories ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . "\n";
}

$conn->close();
?> 