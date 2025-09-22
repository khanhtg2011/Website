<?php
// album_manager.php - Handle album creation and deletion
require __DIR__ . "/config.php";

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit;
}

if (!isset($_POST['csrf']) || $_POST['csrf'] !== csrf_token()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF error']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $albumName = trim($_POST['album_name'] ?? '');
    if (empty($albumName)) {
        echo json_encode(['success' => false, 'error' => 'Album name is required']);
        exit;
    }

    // Check if album already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM photos WHERE album = ?");
    $stmt->bind_param("s", $albumName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'error' => 'Album already exists']);
        exit;
    }

    // Create album by inserting a placeholder record
    $stmt = $conn->prepare("INSERT INTO photos (filename, album, uploaded_at, uploader, ip_address) VALUES (?, ?, NOW(), ?, ?)");
    $filename = '.album_placeholder_' . time();
    $uploader = $_SESSION['username'] ?? 'admin';
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("ssss", $filename, $albumName, $uploader, $ip);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Album created successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create album']);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $albumName = trim($_POST['album_name'] ?? '');
    if (empty($albumName)) {
        echo json_encode(['success' => false, 'error' => 'Album name is required']);
        exit;
    }

    // Get all photos in the album
    $stmt = $conn->prepare("SELECT filename FROM photos WHERE album = ?");
    $stmt->bind_param("s", $albumName);
    $stmt->execute();
    $result = $stmt->get_result();

    $filesToDelete = [];
    while ($row = $result->fetch_assoc()) {
        $filesToDelete[] = $row['filename'];
    }

    // Delete physical files
    foreach ($filesToDelete as $filename) {
        $filePath = __DIR__ . "/uploads/" . $filename;
        $thumbPath = __DIR__ . "/uploads/thumbs/" . $filename;
        $jsonPath = $filePath . ".json";

        if (file_exists($filePath)) unlink($filePath);
        if (file_exists($thumbPath)) unlink($thumbPath);
        if (file_exists($jsonPath)) unlink($jsonPath);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM photos WHERE album = ?");
    $stmt->bind_param("s", $albumName);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Album deleted successfully', 'deleted_count' => count($filesToDelete)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete album']);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>