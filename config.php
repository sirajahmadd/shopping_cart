<?php
$host = 'localhost';
$db   = 'shopping_cart';
$user = 'root'; // default XAMPP user
$pass = '9966';     // default XAMPP password is empty

// $host = 'sql106.infinityfree.com';
// $db   = 'if0_39446750_shopping_cart';
// $user = 'if0_39446750';
// $pass = 'bcgtyYUJhag12';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>