<?php
// albums.php - API for album management
require __DIR__ . "/config.php";

$isAdmin = !empty($_SESSION['is_admin']);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all albums - allow all users to view
        $tableExists = $conn->query("SHOW TABLES LIKE 'albums'")->num_rows > 0;

        if (!$tableExists) {
            header('Content-Type: application/json');
            echo json_encode([['id' => 1, 'name' => 'General', 'description' => 'Default album']]);
            break;
        }

        $result = $conn->query("SELECT * FROM albums ORDER BY created_at DESC");
        $albums = [];
        while ($row = $result->fetch_assoc()) {
            $albums[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($albums);
        break;

    case 'POST':
        // Create new album - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required for album management']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $csrf = $data['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Album name is required']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO albums (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);

        if ($stmt->execute()) {
            $album_id = $conn->insert_id;
            echo json_encode(['success' => true, 'album_id' => $album_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create album']);
        }
        $stmt->close();
        break;

    case 'PUT':
        // Update album - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required for album management']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $csrf = $data['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $album_id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($album_id) || empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Album ID and name are required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE albums SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $album_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update album']);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Delete album - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required for album management']);
            exit;
        }

        $csrf = $_GET['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $album_id = (int)($_GET['id'] ?? 0);

        if (empty($album_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Album ID is required']);
            exit;
        }

        // First, set album_id to NULL for all photos in this album
        $stmt = $conn->prepare("UPDATE photos SET album_id = NULL WHERE album_id = ?");
        $stmt->bind_param("i", $album_id);
        $stmt->execute();
        $stmt->close();

        // Then delete the album
        $stmt = $conn->prepare("DELETE FROM albums WHERE id = ?");
        $stmt->bind_param("i", $album_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete album']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>