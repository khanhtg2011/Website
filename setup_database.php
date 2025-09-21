<?php
// setup_database.php - Auto-setup album system
require __DIR__ . "/config.php";

echo "<h1>Album System Database Setup</h1>";

// Check connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully<br>";
}

// Create albums table
echo "<h2>Creating Albums Table...</h2>";
$sql = "CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    password VARCHAR(255),
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Albums table created successfully<br>";
} else {
    echo "❌ Error creating albums table: " . $conn->error . "<br>";
}

// Add album_id column to photos table
echo "<h2>Updating Photos Table...</h2>";
$sql = "ALTER TABLE photos ADD COLUMN IF NOT EXISTS album_id INT";
if ($conn->query($sql) === TRUE) {
    echo "✅ Album_id column added to photos table<br>";
} else {
    echo "❌ Error adding album_id column: " . $conn->error . "<br>";
}

// Add password column to albums table
echo "<h2>Adding Password Column to Albums Table...</h2>";
$sql = "ALTER TABLE albums ADD COLUMN IF NOT EXISTS password VARCHAR(255)";
if ($conn->query($sql) === TRUE) {
    echo "✅ Password column added to albums table<br>";
} else {
    echo "❌ Error adding password column: " . $conn->error . "<br>";
}

// Clean up invalid album_id references before adding constraint
echo "<h2>Cleaning up invalid album references...</h2>";
$sql = "UPDATE photos SET album_id = NULL WHERE album_id IS NOT NULL AND album_id NOT IN (SELECT id FROM albums)";
if ($conn->query($sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "✅ Cleaned up $affected invalid album references<br>";
} else {
    echo "⚠️ Error cleaning up references: " . $conn->error . "<br>";
}

// Add foreign key constraint
echo "<h2>Adding Foreign Key Constraint...</h2>";
$sql = "ALTER TABLE photos ADD CONSTRAINT fk_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL";
if ($conn->query($sql) === TRUE) {
    echo "✅ Foreign key constraint added<br>";
} else {
    // Check if the error is because the constraint already exists
    if (strpos($conn->error, 'Duplicate key name') !== false || strpos($conn->error, 'already exists') !== false) {
        echo "ℹ️ Foreign key constraint already exists<br>";
    } else {
        echo "⚠️ Foreign key constraint failed: " . $conn->error . "<br>";
    }
}

// Create default album
echo "<h2>Creating Default Album...</h2>";
$sql = "INSERT IGNORE INTO albums (name, description) VALUES ('General', 'Default album for existing photos')";
if ($conn->query($sql) === TRUE) {
    echo "✅ Default 'General' album created<br>";
} else {
    echo "❌ Error creating default album: " . $conn->error . "<br>";
}

// Test the setup
echo "<h2>Testing Setup...</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM albums");
$row = $result->fetch_assoc();
echo "✅ Albums table has " . $row['count'] . " albums<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM photos");
$row = $result->fetch_assoc();
echo "✅ Photos table has " . $row['count'] . " photos<br>";

echo "<h2>🎉 Setup Complete!</h2>";
echo "<p>Your album system is now ready to use!</p>";
echo "<p><a href='/'>← Back to Gallery</a></p>";

$conn->close();
?>