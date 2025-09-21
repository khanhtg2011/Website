<?php
// Test gallery status
echo "<h1>Gallery Status Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h2>Database Connection:</h2>";
require __DIR__ . "/config.php";
if ($db_available) {
    echo "<p class='success'>✅ Database connected</p>";
} else {
    echo "<p class='error'>❌ Database not connected</p>";
}

echo "<h2>Photo Count:</h2>";
if ($db_available) {
    $result = $conn->query("SELECT COUNT(*) as total FROM photos");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p class='info'>📊 Total photos: " . $row['total'] . "</p>";
    } else {
        echo "<p class='error'>❌ Cannot query photos table</p>";
    }
}

echo "<h2>GD Extension:</h2>";
if (function_exists('imagecreatefromjpeg')) {
    echo "<p class='success'>✅ GD extension available</p>";
} else {
    echo "<p class='error'>❌ GD extension not available - thumbnails won't work</p>";
}

echo "<h2>File Permissions:</h2>";
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

if (is_dir($uploadDir)) {
    echo "<p class='success'>✅ Uploads directory exists</p>";
    if (is_readable($uploadDir)) {
        echo "<p class='success'>✅ Uploads directory readable</p>";
    } else {
        echo "<p class='error'>❌ Uploads directory not readable</p>";
    }
} else {
    echo "<p class='error'>❌ Uploads directory does not exist</p>";
}

if (is_dir($thumbDir)) {
    echo "<p class='success'>✅ Thumbs directory exists</p>";
    if (is_writable($thumbDir)) {
        echo "<p class='success'>✅ Thumbs directory writable</p>";
    } else {
        echo "<p class='error'>❌ Thumbs directory not writable</p>";
    }
} else {
    echo "<p class='error'>❌ Thumbs directory does not exist</p>";
}

echo "<h2>Recent Photos:</h2>";
if ($db_available) {
    $result = $conn->query("SELECT filename, uploaded_at FROM photos ORDER BY uploaded_at DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            $filePath = $uploadDir . $row['filename'];
            $exists = file_exists($filePath) ? "✅" : "❌";
            echo "<li>$exists " . htmlspecialchars($row['filename']) . " (" . $row['uploaded_at'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='info'>No photos found in database</p>";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Open your gallery in a browser</li>";
echo "<li>Check browser console (F12) for any JavaScript errors</li>";
echo "<li>Verify that photos are loading and centered</li>";
echo "<li>Look for the 'Load More Photos' button at the bottom</li>";
echo "</ol>";
?>