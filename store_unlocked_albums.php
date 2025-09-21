<?php
session_start();
require __DIR__ . "/config.php";

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get the JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['unlocked_albums'])) {
    http_response_code(400);
    exit('Invalid data');
}

// Store unlocked albums in session
$_SESSION['unlocked_albums'] = $data['unlocked_albums'];

error_log("UNLOCKED ALBUMS: Stored " . count($data['unlocked_albums']) . " unlocked albums in session");

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>