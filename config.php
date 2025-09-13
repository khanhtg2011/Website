<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('private');
    session_start();
}

// Kiểm tra timeout (15 phút = 900 giây) - nhưng không redirect cho AJAX requests
$timeout = 900;
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    if (!$isAjax) {
        // Only redirect for regular page requests, not AJAX
        session_unset();
        session_destroy();
        header("Location: /");
        exit;
    }
    // For AJAX requests, just unset the admin status but don't redirect
    unset($_SESSION['is_admin']);
}

// Cập nhật thời gian hoạt động
$_SESSION['last_activity'] = time();

// Cấu hình MySQL
$DB_HOST = "localhost"; // Thay bằng hostname từ hosting (ví dụ: mysqlXX.000webhost.com)
$DB_USER = "";
$DB_PASS = "";
$DB_NAME = "";

// Kết nối MySQL với debug
$conn = null;
$db_available = false;

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("DB connection failed: " . $conn->connect_error . " (Error code: " . $conn->connect_errno . ")");
    }
    $db_available = true;
} catch (Exception $e) {
    // Database not available - continue without it for demo purposes
    $db_available = false;
    error_log("Database connection failed: " . $e->getMessage() . " - Running in demo mode without database.");
}

// Global variable to check if database is available
define('DB_AVAILABLE', $db_available);

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
function csrf_token() {
    return $_SESSION['csrf_token'];
}