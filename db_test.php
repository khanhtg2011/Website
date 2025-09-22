<?php
// db_test.php - Test database connection and query
require __DIR__ . "/config.php";

echo "<h1>Database Connection Test</h1>";

echo "<h2>Connection Details</h2>";
echo "<ul>";
echo "<li>Host: $DB_HOST</li>";
echo "<li>Database: $DB_NAME</li>";
echo "<li>User: $DB_USER</li>";
echo "<li>DB_AVAILABLE: " . (DB_AVAILABLE ? 'true' : 'false') . "</li>";
echo "</ul>";

if (!DB_AVAILABLE) {
    echo "<p style='color: red;'>❌ Database not available (running in demo mode)</p>";
    echo "<p>This explains why the gallery shows no photos!</p>";
    exit;
}

echo "<h2>Connection Test</h2>";

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    echo "<p style='color: red;'>Error code: " . $conn->connect_errno . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";

    // Test basic query
    echo "<h2>Table Check</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'photos'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ 'photos' table exists</p>";

        // Count photos
        $countResult = $conn->query("SELECT COUNT(*) as count FROM photos");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
            echo "<p style='color: green;'>✅ Found $count photos in database</p>";

            if ($count > 0) {
                // Show sample photo
                echo "<h2>Sample Photo Data</h2>";
                $sampleResult = $conn->query("SELECT filename, uploaded_at FROM photos LIMIT 1");
                if ($sampleResult) {
                    $photo = $sampleResult->fetch_assoc();
                    echo "<pre>" . json_encode($photo, JSON_PRETTY_PRINT) . "</pre>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No photos found in database</p>";
                echo "<p>You need to upload some photos first!</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to count photos: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ 'photos' table does not exist</p>";
        echo "<p>You need to run the database setup!</p>";
    }

    $conn->close();
}

echo "<h2>Recommendations</h2>";
echo "<ol>";
if (!DB_AVAILABLE) {
    echo "<li><strong>Fix database connection</strong> - Check your database credentials in config.php</li>";
}
if (DB_AVAILABLE && $conn->connect_error) {
    echo "<li><strong>Check database credentials</strong> - Verify username, password, and database name</li>";
}
if (DB_AVAILABLE && !$conn->connect_error) {
    echo "<li><strong>Run database setup</strong> - Execute setup_database.php to create tables</li>";
    echo "<li><strong>Upload photos</strong> - Add some photos to see thumbnails</li>";
}
echo "</ol>";
?>