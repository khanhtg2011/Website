<?php
// admin_users.php - Admin user management API
require __DIR__ . "/config.php";

$isAdmin = !empty($_SESSION['is_admin']);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all admin users - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }

        if (!DB_AVAILABLE) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $result = $conn->query("SELECT id, username, created_at, last_login, is_active FROM admin_users ORDER BY created_at DESC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($users);
        break;

    case 'POST':
        // Handle different POST actions
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            switch ($action) {
                case 'login':
                    handleLogin();
                    break;
                case 'create':
                    handleCreateUser();
                    break;
                case 'change_password':
                    handleChangePassword();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Action required']);
        }
        break;

    case 'PUT':
        // Update user - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }

        if (!DB_AVAILABLE) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Database not available']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['id'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 1);

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE admin_users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $isActive, $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user']);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Delete user - Admin only
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }

        if (!DB_AVAILABLE) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Database not available']);
            exit;
        }

        $userId = (int)($_GET['id'] ?? 0);

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            exit;
        }

        // Don't allow deleting yourself
        $currentUserId = $_SESSION['admin_user_id'] ?? 0;
        if ($userId == $currentUserId) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleLogin() {
    global $conn;

    if (!DB_AVAILABLE) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database not available']);
        exit;
    }

    header('Content-Type: application/json');

    // Handle both JSON and FormData input
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
    } else {
        // Handle FormData
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
    }

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }

    // Check login attempts
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

    $stmt = $conn->prepare("SELECT id, password, is_active FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $_SESSION['login_attempts']++;
        $attempts_left = 3 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['login_attempts'] = 0;
            $attempts_left = 3;
        }

        http_response_code(401);
        echo json_encode([
            'error' => "Invalid username or password! (Attempt " . $_SESSION['login_attempts'] . "/3)",
            'attempts_left' => $attempts_left
        ]);
        exit;
    }

    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(['error' => 'Account is disabled']);
        exit;
    }

    if ($password === $user['password']) {
        // Login successful
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['login_attempts'] = 0;

        // Update last login
        $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'username' => $username]);
    } else {
        $_SESSION['login_attempts']++;
        $attempts_left = 3 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['login_attempts'] = 0;
            $attempts_left = 3;
        }

        http_response_code(401);
        echo json_encode([
            'error' => "Invalid username or password! (Attempt " . $_SESSION['login_attempts'] . "/3)",
            'attempts_left' => $attempts_left
        ]);
    }
}

function handleCreateUser() {
    global $conn, $isAdmin;

    if (!$isAdmin) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }

    if (!DB_AVAILABLE) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database not available']);
        exit;
    }

    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }

    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be at least 3 characters']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit;
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }
    $stmt->close();

    // Create user (store password as plain text)
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo json_encode(['success' => true, 'user_id' => $userId, 'username' => $username]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
    $stmt->close();
}

function handleChangePassword() {
    global $conn, $isAdmin;

    if (!$isAdmin) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }

    if (!DB_AVAILABLE) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database not available']);
        exit;
    }

    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'Current and new password required']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        exit;
    }

    $userId = $_SESSION['admin_user_id'] ?? 0;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || $currentPassword !== $user['password']) {
        http_response_code(401);
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    // Update password (store as plain text)
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $newPassword, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
    }
    $stmt->close();
}
?>