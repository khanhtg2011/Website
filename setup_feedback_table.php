<?php
// setup_feedback_table.php - Create the photo_feedback table
require __DIR__ . "/config.php";

echo "<h1>Setting up Photo Feedback System</h1>";

// Create photo_feedback table
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
    echo "<p style='color: green;'>✅ Photo feedback table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
}

// Test the table
$result = $conn->query("SHOW TABLES LIKE 'photo_feedback'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Photo feedback table exists and is ready!</p>";

    // Insert some test data
    $testData = [
        ['test_photo1.jpg', 5, 'Amazing photo!', '127.0.0.1'],
        ['test_photo1.jpg', 4, 'Great composition', '127.0.0.2'],
        ['test_photo2.jpg', 3, 'Nice colors', '127.0.0.1'],
    ];

    foreach ($testData as $data) {
        $stmt = $conn->prepare("INSERT IGNORE INTO photo_feedback (photo_filename, rating, comment, user_ip) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $data[0], $data[1], $data[2], $data[3]);
        $stmt->execute();
        $stmt->close();
    }

    echo "<p style='color: green;'>✅ Test data inserted!</p>";
} else {
    echo "<p style='color: red;'>❌ Photo feedback table does not exist!</p>";
}

echo "<h2>Testing Feedback API</h2>";

// Test GET request
echo "<h3>Testing GET /feedback.php?photo=test_photo1.jpg</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/feedback.php?photo=test_photo1.jpg");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 && json_decode($response) !== null) {
    echo "<p style='color: green;'>✅ Feedback API is working correctly!</p>";
} else {
    echo "<p style='color: red;'>❌ Feedback API has issues. Check the response above.</p>";
}

$conn->close();
?>