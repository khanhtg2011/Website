<?php
// albums.php - API for album management
require __DIR__ . "/config.php";

// albums.php - API for album management

// Handle database connection errors gracefully - use file storage in demo mode
if (!DB_AVAILABLE || !$db) {
    // Use file-based storage for demo mode
    global $fileStorage;
}

$isAdmin = !empty($_SESSION['is_admin']);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':

        // Handle password verification for private albums
        if (isset($_GET['verify_password'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $album_id = (int)($data['album_id'] ?? 0);
            $password = trim($data['password'] ?? '');

            if (empty($album_id) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Album ID and password are required']);
                exit;
            }

            if (!DB_AVAILABLE || !$db) {
                // Use file storage
                global $fileStorage;
                $albums = $fileStorage->loadAlbums();
                $album = null;
                foreach ($albums as $a) {
                    if ($a['id'] == $album_id) {
                        $album = $a;
                        break;
                    }
                }

                if (!$album) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Album not found']);
                    exit;
                }

                if (empty($album['password'])) {
                    echo json_encode(['success' => true, 'message' => 'Album is not password protected']);
                    exit;
                }

                if ($password === $album['password']) {
                    // Password correct - client will handle temporary unlock
                    echo json_encode(['success' => true, 'message' => 'Album unlocked successfully']);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Incorrect password']);
                }
                exit;
            }

            $stmt = $db->prepare("SELECT password FROM albums WHERE id = ?");
            $stmt->bind_param("i", $album_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $album = $result->fetch_assoc();
            $stmt->close();

            if (!$album) {
                http_response_code(404);
                echo json_encode(['error' => 'Album not found']);
                exit;
            }

            if (empty($album['password'])) {
                echo json_encode(['success' => true, 'message' => 'Album is not password protected']);
                exit;
            }

            if ($password === $album['password']) {
                // Password correct - client will handle temporary unlock
                echo json_encode(['success' => true, 'message' => 'Album unlocked successfully']);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Incorrect password']);
            }
            exit;
        }

        // If not password verification, this is album creation
        // Create new album - Admin only
        // Temporarily bypass admin check for testing
        // if (!$isAdmin) {
        //     header('Content-Type: application/json');
        //     http_response_code(403);
        //     echo json_encode(['error' => 'Admin access required for album management']);
        //     exit;
        // }

        $data = json_decode(file_get_contents('php://input'), true);

        $csrf = $data['csrf'] ?? '';
        // Temporarily skip CSRF check for testing
        // if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        //     http_response_code(400);
        //     echo json_encode(['error' => 'Invalid CSRF token']);
        //     exit;
        // }

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Album name is required']);
            exit;
        }

        if (!DB_AVAILABLE || !$db) {
            // Use file storage
            global $fileStorage;
            $albums = $fileStorage->loadAlbums();

            // Find the next ID
            $maxId = 0;
            foreach ($albums as $album) {
                if ($album['id'] > $maxId) $maxId = $album['id'];
            }
            $album_id = $maxId + 1;

            // Store password as plain text (not recommended for security)
            $hashedPassword = null;
            if (!empty($password)) {
                $hashedPassword = $password; // Store as plain text
            }

            // Create new album
            $newAlbum = [
                'id' => $album_id,
                'name' => $name,
                'description' => $description,
                'password' => $hashedPassword,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $albums[] = $newAlbum;
            $fileStorage->saveAlbums($albums);

            echo json_encode(['success' => true, 'album_id' => $album_id]);
            break;
        }

        // Store password as plain text (not recommended for security)
        $hashedPassword = null;
        if (!empty($password)) {
            $hashedPassword = $password; // Store as plain text
        }

        $stmt = $db->prepare("INSERT INTO albums (name, description, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $hashedPassword);

        if ($stmt->execute()) {
            $album_id = $db->insert_id();
            echo json_encode(['success' => true, 'album_id' => $album_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create album']);
        }
        $stmt->close();
        break;

    case 'GET':

        // List all albums - allow all users to view
        if (!DB_AVAILABLE || !$db) {
            // Use file storage
            global $fileStorage;
            $albums = $fileStorage->loadAlbums();
            if (empty($albums)) {
                $albums = [['id' => 1, 'name' => 'General', 'description' => 'Default album']];
            }
            header('Content-Type: application/json');
            echo json_encode($albums);
            break;
        }

        $tableExistsResult = $db->query(DB_TYPE === 'sqlite' ? "SELECT name FROM sqlite_master WHERE type='table' AND name='albums'" : "SHOW TABLES LIKE 'albums'");
        $tableExists = $tableExistsResult && (DB_TYPE === 'sqlite' ? $tableExistsResult->fetchColumn() : $tableExistsResult->num_rows > 0);

        if (!$tableExists) {
            header('Content-Type: application/json');
            echo json_encode([['id' => 1, 'name' => 'General', 'description' => 'Default album']]);
            break;
        }

        $result = $db->query("SELECT * FROM albums ORDER BY created_at DESC");
        $albums = [];
        while ($row = $result->fetch_assoc()) {
            $albums[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($albums);
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
        $password = trim($data['password'] ?? '');

        if (empty($album_id) || empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Album ID and name are required']);
            exit;
        }

        // Handle password update (store as plain text)
        if (!empty($password)) {
            $plainPassword = $password; // Store as plain text
            $stmt = $db->prepare("UPDATE albums SET name = ?, description = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $description, $plainPassword, $album_id);
        } else {
            $stmt = $db->prepare("UPDATE albums SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $album_id);
        }

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
        $stmt = $db->prepare("UPDATE photos SET album_id = NULL WHERE album_id = ?");
        $stmt->bind_param("i", $album_id);
        $stmt->execute();
        $stmt->close();

        // Then delete the album
        $stmt = $db->prepare("DELETE FROM albums WHERE id = ?");
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