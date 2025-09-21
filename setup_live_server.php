<?php
// setup_live_server.php - Setup feedback system on live server
require __DIR__ . "/config.php";

echo "<h1>Setting up Feedback System on Live Server</h1>";

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
    exit;
}

// Verify table exists
$result = $conn->query("SHOW TABLES LIKE 'photo_feedback'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Photo feedback table verified and ready!</p>";

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
    echo "<p style='color: red;'>❌ Table creation failed!</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>1. The feedback system is now set up on your live server</p>";
echo "<p>2. Test the feedback functionality by:</p>";
echo "<ul>";
echo "<li>Opening a photo in the gallery</li>";
echo "<li>Clicking on stars to rate the photo</li>";
echo "<li>Adding a comment (optional)</li>";
echo "<li>Clicking 'Submit Feedback'</li>";
echo "</ul>";
echo "<p>3. The star rating zoom issue has been fixed with mobile-specific CSS</p>";

$conn->close();
?>