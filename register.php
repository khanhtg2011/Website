<?php
// register.php (simplified)
require 'config.php'; // kết nối $conn, load env

$username = trim($_POST['username']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$pass = $_POST['password'];

if (!$email || strlen($username) < 3) { /* lỗi */ }

if (strlen($pass) < 8) { /* lỗi: yêu cầu mạnh hơn */ }

$hash = password_hash($pass, PASSWORD_DEFAULT);

// Insert với prepared statement
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hash);
$stmt->execute();
