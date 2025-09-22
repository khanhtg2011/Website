<?php
// login.php (simplified)
require 'config.php'; // $conn

$username = $_POST['username'];
$pass = $_POST['password'];
$ip = $_SERVER['REMOTE_ADDR'];

// 1) check brute force: count last N minutes fails
$stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)");
$stmt->bind_param("s", $ip); $stmt->execute(); $stmt->bind_result($fail_count); $stmt->fetch(); $stmt->close();

if ($fail_count >= 5) {
    // reject + tell user wait
    http_response_code(429);
    echo "Too many attempts, please wait 15 minutes.";
    exit;
}

// 2) get user
$stmt = $conn->prepare("SELECT id, password_hash, is_active FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($id, $password_hash, $is_active);
if (!$stmt->fetch()) {
    // write failed attempt, but do not reveal whether username exists
    $stmt->close();
    $s = $conn->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, 0)");
    $s->bind_param("ss", $ip, $username); $s->execute(); $s->close();
    http_response_code(401); echo "Invalid credentials.";
    exit;
}
$stmt->close();

if (!$is_active) { http_response_code(403); echo "Account disabled."; exit; }

if (!password_verify($pass, $password_hash)) {
    // log fail
    $s = $conn->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, 0)");
    $s->bind_param("ss", $ip, $username); $s->execute(); $s->close();
    http_response_code(401); echo "Invalid credentials."; exit;
}

// success: clear attempts, create session
$s = $conn->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, 1)");
$s->bind_param("ss", $ip, $username); $s->execute(); $s->close();

session_regenerate_id(true);
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username;

// set secure cookie params in config (see below)
