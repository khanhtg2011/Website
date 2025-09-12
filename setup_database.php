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

// Add foreign key constraint
echo "<h2>Adding Foreign Key Constraint...</h2>";
$sql = "ALTER TABLE photos ADD CONSTRAINT IF NOT EXISTS fk_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL";
if ($conn->query($sql) === TRUE) {
    echo "✅ Foreign key constraint added<br>";
} else {
    echo "⚠️ Foreign key constraint may already exist or failed: " . $conn->error . "<br>";
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