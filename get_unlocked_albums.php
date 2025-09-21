<?php
session_start();

// Return unlocked albums from session
header('Content-Type: application/json');

$unlocked_albums = $_SESSION['unlocked_albums'] ?? [];

error_log("GET UNLOCKED ALBUMS: Retrieved " . count($unlocked_albums) . " unlocked albums from session");

echo json_encode(['unlocked_albums' => $unlocked_albums]);
?>