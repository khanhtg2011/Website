<?php
session_start();
require __DIR__ . "/config.php";

// CSRF verification function
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if user is admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf']) || !verify_csrf_token($_POST['csrf'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$filename = $_POST['filename'] ?? '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'error' => 'Filename is required']);
    exit;
}

try {
    switch ($action) {
        case 'select':
            $selected = isset($_POST['selected']) ? (bool)$_POST['selected'] : false;
            $stmt = $conn->prepare("UPDATE photos SET selected = ? WHERE filename = ?");
            $stmt->execute([$selected ? 1 : 0, $filename]);
            echo json_encode(['success' => true]);
            break;

        case 'favorite':
            $favorite = isset($_POST['favorite']) ? (bool)$_POST['favorite'] : false;
            $stmt = $conn->prepare("UPDATE photos SET favorite = ? WHERE filename = ?");
            $stmt->execute([$favorite ? 1 : 0, $filename]);
            echo json_encode(['success' => true]);
            break;

        case 'print':
            $size = $_POST['size'] ?? '';
            $stmt = $conn->prepare("UPDATE photos SET print_size = ? WHERE filename = ?");
            $stmt->execute([$size === 'Bỏ chọn' ? null : $size, $filename]);
            echo json_encode(['success' => true]);
            break;

        case 'note':
            $note = $_POST['note'] ?? '';
            $stmt = $conn->prepare("UPDATE photos SET note = ? WHERE filename = ?");
            $stmt->execute([$note, $filename]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Photo actions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>