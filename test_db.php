<?php
// test_db.php - Test database connection and album system
require __DIR__ . "/config.php";

echo "<h1>Database Connection Test</h1>";

// Test connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully<br>";
}

// Test tables
$tables = ['photos', 'albums', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' does not exist<br>";
    }
}

// Test photos query
echo "<h2>Testing Photos Query</h2>";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM photos");
    $row = $result->fetch_assoc();
    echo "✅ Photos table has " . $row['count'] . " records<br>";
} catch (Exception $e) {
    echo "❌ Photos query failed: " . $e->getMessage() . "<br>";
}

// Test albums query
echo "<h2>Testing Albums Query</h2>";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM albums");
    $row = $result->fetch_assoc();
    echo "✅ Albums table has " . $row['count'] . " records<br>";
} catch (Exception $e) {
    echo "❌ Albums query failed: " . $e->getMessage() . "<br>";
}

// Test JOIN query
echo "<h2>Testing JOIN Query</h2>";
try {
    $result = $conn->query("SELECT p.filename, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id LIMIT 5");
    echo "✅ JOIN query successful<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['filename'] . " (Album: " . ($row['album_name'] ?? 'General') . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ JOIN query failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Quick Fix</h2>";
echo "<p>If you're seeing errors, run these SQL commands in your database:</p>";
echo "<pre>";
echo "-- Create albums table if it doesn't exist
CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add album_id column to photos if it doesn't exist
ALTER TABLE photos ADD COLUMN IF NOT EXISTS album_id INT;
ALTER TABLE photos ADD CONSTRAINT IF NOT EXISTS fk_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL;

-- Create default album
INSERT IGNORE INTO albums (name, description) VALUES ('General', 'Default album for existing photos');
";
echo "</pre>";

$conn->close();
?>