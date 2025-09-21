<?php
// add_album_password.php - Migration script to add password column to albums table
require __DIR__ . "/config.php";

if (!DB_AVAILABLE) {
    die("Database not available. Cannot run migration.\n");
}

echo "Adding password column to albums table...\n";

// Check if password column already exists
$result = $conn->query("SHOW COLUMNS FROM albums LIKE 'password'");
if ($result->num_rows > 0) {
    echo "Password column already exists. Migration complete.\n";
    exit;
}

// Add password column
$sql = "ALTER TABLE albums ADD COLUMN password VARCHAR(255) NULL AFTER description";
if ($conn->query($sql) === TRUE) {
    echo "Password column added successfully to albums table.\n";
    echo "Migration complete! You can now create password-protected albums.\n";
} else {
    echo "Error adding password column: " . $conn->error . "\n";
}

$conn->close();
?>