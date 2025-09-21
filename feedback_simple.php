<?php
// feedback_simple.php - Simplified feedback API for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Simple error handler
function sendError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
}

// Simple success handler
function sendSuccess($data) {
    echo json_encode(array_merge($data, ['timestamp' => date('Y-m-d H:i:s')]));
    exit;
}

try {
    // Check request method
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'])) {
        sendError('Method not allowed', 405);
    }

    // For GET requests
    if ($method === 'GET') {
        $photo = $_GET['photo'] ?? '';
        if (empty($photo)) {
            sendError('Photo parameter required', 400);
        }

        // Return mock data for testing
        sendSuccess([
            'stats' => [
                'total_ratings' => 0,
                'average_rating' => null,
                'five_stars' => 0,
                'four_stars' => 0,
                'three_stars' => 0,
                'two_stars' => 0,
                'one_star' => 0
            ],
            'comments' => [],
            'message' => 'Simple feedback API working',
            'photo' => $photo
        ]);
    }

    // For POST requests
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError('Invalid JSON data', 400);
        }

        $photo = $data['photo'] ?? '';
        $rating = (int)($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (empty($photo) || $rating < 1 || $rating > 5) {
            sendError('Invalid data: photo and rating (1-5) required', 400);
        }

        sendSuccess([
            'success' => true,
            'message' => 'Feedback received (simplified version)',
            'data' => [
                'photo' => $photo,
                'rating' => $rating,
                'comment' => $comment,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]
        ]);
    }

} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
?>