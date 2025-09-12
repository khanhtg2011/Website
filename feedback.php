<?php
// feedback.php - Handle photo feedback/ratings
require __DIR__ . "/config.php";

// Set JSON content type for all responses
header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get feedback for a specific photo
        $photo_filename = $_GET['photo'] ?? '';
        $check_user = isset($_GET['check_user']);
        $user_ip = $_SERVER['REMOTE_ADDR'];

        if (empty($photo_filename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Photo filename required']);
            exit;
        }

        // If just checking if user has feedback, return simple response
        if ($check_user) {
            $tableExists = $conn->query("SHOW TABLES LIKE 'photo_feedback'")->num_rows > 0;
            if (!$tableExists) {
                echo json_encode(['has_feedback' => false]);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM photo_feedback
                WHERE photo_filename = ? AND user_ip = ?
            ");
            $stmt->bind_param("ss", $photo_filename, $user_ip);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            echo json_encode(['has_feedback' => $row['count'] > 0]);
            exit;
        }

        // Check if table exists first
        $tableExists = $conn->query("SHOW TABLES LIKE 'photo_feedback'")->num_rows > 0;
        if (!$tableExists) {
            echo json_encode([
                'stats' => [
                    'total_ratings' => 0,
                    'average_rating' => null,
                    'five_stars' => 0,
                    'four_stars' => 0,
                    'three_stars' => 0,
                    'two_stars' => 0,
                    'one_star' => 0
                ],
                'comments' => []
            ]);
            exit;
        }

        // Get average rating and feedback count
        $stmt = $conn->prepare("
            SELECT
                COUNT(*) as total_ratings,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_stars,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_stars,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_stars,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            FROM photo_feedback
            WHERE photo_filename = ?
        ");
        if (!$stmt) {
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("s", $photo_filename);
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
            exit;
        }
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        // Get recent comments (last 10)
        $stmt = $conn->prepare("
            SELECT id, rating, comment, created_at, user_ip
            FROM photo_feedback
            WHERE photo_filename = ? AND comment IS NOT NULL AND comment != ''
            ORDER BY created_at DESC
            LIMIT 10
        ");
        if (!$stmt) {
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("s", $photo_filename);
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
            exit;
        }
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'stats' => $stats,
            'comments' => $comments
        ]);
        break;

    case 'POST':
        // Submit feedback for a photo
        $data = json_decode(file_get_contents('php://input'), true);
        $photo_filename = $data['photo'] ?? '';
        $rating = (int)($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (empty($photo_filename) || $rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        // Check if table exists first
        $tableExists = $conn->query("SHOW TABLES LIKE 'photo_feedback'")->num_rows > 0;
        if (!$tableExists) {
            http_response_code(500);
            echo json_encode(['error' => 'Feedback system not set up yet']);
            exit;
        }

        // Check if user already rated this photo (by IP, to prevent spam)
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM photo_feedback
            WHERE photo_filename = ? AND user_ip = ?
        ");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ss", $photo_filename, $user_ip);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
            exit;
        }
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();

        if ($existing['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already rated this photo']);
            exit;
        }

        // Insert new feedback
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conn->prepare("
            INSERT INTO photo_feedback (photo_filename, rating, comment, user_ip, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sisss", $photo_filename, $rating, $comment, $user_ip, $user_agent);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save feedback: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Delete feedback for a specific photo
        $data = json_decode(file_get_contents('php://input'), true);
        $photo_filename = $data['photo'] ?? '';
        $feedback_id = $data['feedback_id'] ?? null; // For admin deletion by ID
        $user_ip = $_SERVER['REMOTE_ADDR'];

        if (empty($photo_filename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Photo filename required']);
            exit;
        }

        // Check if table exists first
        $tableExists = $conn->query("SHOW TABLES LIKE 'photo_feedback'")->num_rows > 0;
        if (!$tableExists) {
            http_response_code(500);
            echo json_encode(['error' => 'Feedback system not set up yet']);
            exit;
        }

        // Check if user is admin (from session)
        $is_admin = false;
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            $is_admin = true;
        }

        if ($is_admin && $feedback_id) {
            // Admin can delete any feedback by ID
            $stmt = $conn->prepare("
                DELETE FROM photo_feedback
                WHERE id = ? AND photo_filename = ?
            ");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("is", $feedback_id, $photo_filename);
        } else {
            // Regular user can only delete their own feedback
            $stmt = $conn->prepare("
                DELETE FROM photo_feedback
                WHERE photo_filename = ? AND user_ip = ?
            ");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("ss", $photo_filename, $user_ip);
        }

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No feedback found to delete']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete feedback: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>