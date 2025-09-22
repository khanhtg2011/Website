<?php
session_start();
require __DIR__ . "/config.php"; // should initialize $conn
require __DIR__ . "/cache_utils.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["success" => false, "error" => "Method not allowed"]));
}

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    exit(json_encode(["success" => false, "error" => "No permission - not logged in as admin"]));
}

$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(400);
    exit(json_encode(["success" => false, "error" => "Invalid CSRF token"]));
}

$files = $_POST['files'] ?? [];
if (!is_array($files)) $files = [$files];

$success = true;

foreach ($files as $file) {
    $file = basename($file); // sanitize filename
    $path  = __DIR__ . "/uploads/" . $file;
    $thumb = __DIR__ . "/uploads/thumbs/" . $file;
    $webp  = preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $thumb);

    if (file_exists($path) && !unlink($path)) $success = false;
    if (file_exists($thumb) && !unlink($thumb)) $success = false;
    if (file_exists($webp) && !unlink($webp)) $success = false;

    if ($conn) {
        $stmt = $conn->prepare("DELETE FROM photos WHERE filename=?");
        $stmt->bind_param("s", $file);
        if (!$stmt->execute()) {
            $success = false;
        }
        $stmt->close();
    } else {
        $success = false;
    }
}

// Clear cache after successful deletion
if ($success) {
    clearGalleryCache();
}

header("Content-Type: application/json");
echo json_encode(["success" => $success]);
exit;

