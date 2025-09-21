<?php
session_start();

// Load private gallery config if it exists
$configFile = __DIR__ . '/private_gallery_config.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'error' => 'Private gallery not configured']);
    exit;
}

require $configFile;

// Check if private gallery is enabled
if (!defined('PRIVATE_GALLERY_ENABLED') || !PRIVATE_GALLERY_ENABLED) {
    echo json_encode(['success' => false, 'error' => 'Private gallery not enabled']);
    exit;
}

if (!defined('PRIVATE_GALLERY_PASSWORD')) {
    echo json_encode(['success' => false, 'error' => 'Private gallery password not set']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password is required']);
    exit;
}

// Verify password (plain text comparison)
if ($password === PRIVATE_GALLERY_PASSWORD) {
    // Password correct - set session flag for private gallery access
    $_SESSION['private_gallery_access'] = true;
    $_SESSION['private_gallery_access_time'] = time();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Incorrect password']);
}
?>