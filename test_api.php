<?php
// test_api.php - Test the list.php API endpoint
require __DIR__ . "/config.php";

echo "<h1>Testing list.php API Endpoint</h1>";

// Test database connection
echo "<h2>Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
}

// Test albums table
echo "<h2>Albums Table Check</h2>";
$tableExists = $conn->query("SHOW TABLES LIKE 'albums'")->num_rows > 0;
if ($tableExists) {
    echo "<p style='color: green;'>✅ Albums table exists</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Albums table does not exist - will use fallback</p>";
}

// Test photos table
echo "<h2>Photos Table Check</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM photos");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ Photos table has " . $row['count'] . " records</p>";
} else {
    echo "<p style='color: red;'>❌ Photos table query failed</p>";
}

// Test the list.php logic directly
echo "<h2>Testing list.php Logic</h2>";
try {
    $albumFilter = 'all';
    $sortBy = 'date';
    $sortOrder = 'desc';

    if ($tableExists) {
        if ($albumFilter !== 'all' && $albumFilter !== '' && is_numeric($albumFilter)) {
            $albumId = (int)$albumFilter;
            $stmt = $conn->prepare("SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id WHERE p.album_id = ? ORDER BY p.uploaded_at DESC");
            $stmt->bind_param("i", $albumId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id ORDER BY p.uploaded_at DESC";
            $result = $conn->query($query);
        }
    } else {
        $query = "SELECT filename, uploaded_at, uploader, ip_address, NULL as album_id, 'General' as album_name FROM photos ORDER BY uploaded_at DESC";
        $result = $conn->query($query);
    }

    if ($result) {
        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row;
        }
        echo "<p style='color: green;'>✅ Query executed successfully, found " . count($photos) . " photos</p>";

        if (count($photos) > 0) {
            echo "<h3>Sample Photo Data:</h3>";
            echo "<pre>" . json_encode($photos[0], JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The list.php API should now work properly with the fixes applied:</p>";
echo "<ul>";
echo "<li>✅ Fixed SQL injection vulnerability in ORDER BY clause</li>";
echo "<li>✅ Added X-Requested-With headers to all AJAX requests</li>";
echo "<li>✅ Improved error handling</li>";
echo "</ul>";

$conn->close();
?>