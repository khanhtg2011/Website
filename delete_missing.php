<?php
require __DIR__ . "/config.php";

$filename = 'image_1757150879.png';

$stmt = $conn->prepare("DELETE FROM photos WHERE filename = ?");
$stmt->bind_param("s", $filename);
if ($stmt->execute()) {
    echo "Deleted record for $filename\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}
$stmt->close();
$conn->close();
?>