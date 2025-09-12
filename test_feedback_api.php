<?php
// test_feedback_api.php - Test the feedback API to see what's being returned
echo "<h1>Testing Feedback API Response</h1>";

// Test 1: Direct file access
echo "<h2>Test 1: Direct feedback.php access</h2>";
echo "<p>Testing: feedback.php?photo=test.jpg</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/feedback.php?photo=test.jpg");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in response
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Content-Type:</strong> $contentType</p>";
echo "<p><strong>Response Headers:</strong></p>";
echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";

// Test 2: Check if table exists
echo "<h2>Test 2: Database table check</h2>";
require __DIR__ . "/config.php";

$tableExists = $conn->query("SHOW TABLES LIKE 'photo_feedback'")->num_rows > 0;
if ($tableExists) {
    echo "<p style='color: green;'>✅ photo_feedback table exists</p>";

    // Check table structure
    $result = $conn->query("DESCRIBE photo_feedback");
    echo "<p><strong>Table structure:</strong></p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Field']} - {$row['Type']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ photo_feedback table does NOT exist</p>";
    echo "<p><strong>Creating table...</strong></p>";

    $sql = "
    CREATE TABLE IF NOT EXISTS photo_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        photo_filename VARCHAR(255) NOT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        user_ip VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_photo_filename (photo_filename),
        INDEX idx_created_at (created_at)
    );
    ";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

$conn->close();

// Test 3: Check PHP syntax
echo "<h2>Test 3: PHP Syntax Check</h2>";
$feedbackFile = __DIR__ . "/feedback.php";
if (file_exists($feedbackFile)) {
    echo "<p style='color: green;'>✅ feedback.php file exists</p>";

    // Check if file is readable
    if (is_readable($feedbackFile)) {
        echo "<p style='color: green;'>✅ feedback.php is readable</p>";

        // Check file size
        $fileSize = filesize($feedbackFile);
        echo "<p><strong>File size:</strong> $fileSize bytes</p>";

        // Show first few lines
        $lines = file($feedbackFile);
        echo "<p><strong>First 10 lines:</strong></p>";
        echo "<pre>" . htmlspecialchars(implode("", array_slice($lines, 0, 10))) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ feedback.php is not readable</p>";
    }
} else {
    echo "<p style='color: red;'>❌ feedback.php file does not exist</p>";
}

echo "<h2>Test 4: Manual API Call</h2>";
echo "<p>Try this URL directly in your browser:</p>";
echo "<code>https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/feedback.php?photo=test.jpg</code>";

echo "<h2>Test 5: JavaScript Console Test</h2>";
echo "<p>Open browser console (F12) and run:</p>";
echo "<pre>
fetch('feedback.php?photo=test.jpg', {
  headers: {'X-Requested-With': 'XMLHttpRequest'}
})
.then(r => r.text())
.then(t => console.log('Response:', t))
.catch(e => console.error('Error:', e))
</pre>";
?>