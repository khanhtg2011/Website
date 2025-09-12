<?php
require __DIR__ . "/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$_SESSION['is_admin'] = true;
echo json_encode(['ok' => true]);
?>