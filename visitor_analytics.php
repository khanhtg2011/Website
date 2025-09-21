<?php
session_start();
require __DIR__ . "/config.php";

// Only allow admin access
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Handle AJAX requests only
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

header('Content-Type: application/json');

// Simple visitor tracking using a JSON file
$visitorsFile = __DIR__ . '/data/visitors.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Load existing visitor data
$visitors = [];
if (file_exists($visitorsFile)) {
    $data = file_get_contents($visitorsFile);
    $visitors = json_decode($data, true) ?: [];
}

// Clean up old entries (older than 24 hours)
$currentTime = time();
$visitors = array_filter($visitors, function($visitor) use ($currentTime) {
    return ($currentTime - strtotime($visitor['timestamp'])) < 86400; // 24 hours
});

// Calculate analytics
$onlineUsers = 0;
$todayVisits = 0;
$recentVisitors = [];

$today = date('Y-m-d');
foreach ($visitors as $visitor) {
    $visitDate = date('Y-m-d', strtotime($visitor['timestamp']));

    // Count online users (active in last 5 minutes)
    if (($currentTime - strtotime($visitor['timestamp'])) < 300) {
        $onlineUsers++;
    }

    // Count today's visits
    if ($visitDate === $today) {
        $todayVisits++;
    }

    // Collect recent visitors (last 10)
    $recentVisitors[] = $visitor;
}

// Sort recent visitors by timestamp (newest first) and limit to 10
usort($recentVisitors, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
$recentVisitors = array_slice($recentVisitors, 0, 10);

// Save cleaned data
file_put_contents($visitorsFile, json_encode(array_values($visitors), JSON_PRETTY_PRINT));

// Return analytics data
echo json_encode([
    'online_users' => $onlineUsers,
    'today_visits' => $todayVisits,
    'recent_visitors' => $recentVisitors,
    'total_visitors' => count($visitors)
]);
?>