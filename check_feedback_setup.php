<?php
// check_feedback_setup.php - Check if feedback system is properly set up
require __DIR__ . "/config.php";

echo "<h1>Feedback System Diagnostic</h1>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Check if photo_feedback table exists
$result = $conn->query("SHOW TABLES LIKE 'photo_feedback'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Photo feedback table exists</p>";

    // Check table structure
    $result = $conn->query("DESCRIBE photo_feedback");
    if ($result) {
        echo "<p style='color: green;'>✅ Table structure is correct</p>";
        echo "<h3>Table Structure:</h3><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} - {$row['Type']} ({$row['Key']})</li>";
        }
        echo "</ul>";
    }

    // Check if there are any records
    $result = $conn->query("SELECT COUNT(*) as count FROM photo_feedback");
    $count = $result->fetch_assoc()['count'];
    echo "<p style='color: blue;'>📊 Total feedback records: $count</p>";

} else {
    echo "<p style='color: red;'>❌ Photo feedback table does not exist</p>";
    echo "<p><strong>To fix this, run:</strong> <code>setup_feedback_table.php</code></p>";
}

// Test feedback API
echo "<h2>Testing Feedback API</h2>";

// Test GET request
echo "<h3>Testing GET /feedback.php?photo=test.jpg</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://" . $_SERVER['HTTP_HOST'] . "/feedback.php?photo=test.jpg");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-width: 800px; word-wrap: break-word;'>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ API returned valid JSON</p>";
    } else {
        echo "<p style='color: red;'>❌ API returned invalid JSON</p>";
    }
} else {
    echo "<p style='color: red;'>❌ API returned HTTP $httpCode</p>";
}

$conn->close();
?>