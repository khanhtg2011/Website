<?php
// move_photos.php - API to move selected photos to a different album
require __DIR__ . "/config.php";
require __DIR__ . "/cache_utils.php";

$isAdmin = !empty($_SESSION['is_admin']);
if (!$isAdmin) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Check CSRF token from request body
$csrf_token = $data['csrf'] ?? '';
if (empty($csrf_token) || $csrf_token !== csrf_token()) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF error']);
    exit;
}
$photo_filenames = $data['photo_filenames'] ?? [];
$album_id = isset($data['album_id']) ? (int)$data['album_id'] : null;

if (empty($photo_filenames) || !is_array($photo_filenames)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No photos selected']);
    exit;
}

if ($album_id === null) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No album selected']);
    exit;
}

// Check if album exists
$album_check = $conn->prepare("SELECT id FROM albums WHERE id = ?");
$album_check->bind_param("i", $album_id);
$album_check->execute();
if ($album_check->get_result()->num_rows === 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Album not found']);
    exit;
}
$album_check->close();

// Prepare the update statement
$stmt = $conn->prepare("UPDATE photos SET album_id = ? WHERE filename = ?");
$updated_count = 0;

foreach ($photo_filenames as $filename) {
    // Sanitize filename
    $filename = basename($filename);

    $stmt->bind_param("is", $album_id, $filename);
    if ($stmt->execute()) {
        $updated_count++;
    }
}

$stmt->close();

header('Content-Type: application/json');
if ($updated_count > 0) {
    // Clear cache after successful move
    clearGalleryCache();

    echo json_encode([
        'success' => true,
        'message' => "Successfully moved $updated_count photos to album",
        'updated_count' => $updated_count
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No photos were updated']);
}

$conn->close();

?>