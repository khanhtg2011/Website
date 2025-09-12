<?php
// deploy.php - Hostinger Deployment Helper
echo "<h1>🚀 Photo Gallery Deployment Helper</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check PHP version
echo "<h2>📋 System Check</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check required extensions
$required_extensions = ['mysqli', 'gd', 'mbstring', 'json'];
echo "<p><strong>Required Extensions:</strong></p><ul>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '<span class="success">✓ Installed</span>' : '<span class="error">✗ Missing</span>';
    echo "<li>$ext: $status</li>";
}
echo "</ul>";

// Check directory permissions
$dirs_to_check = ['uploads', 'uploads/thumbs', 'cache', 'logs'];
echo "<p><strong>Directory Permissions:</strong></p><ul>";
foreach ($dirs_to_check as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "<li>$dir: <span class='success'>✓ Created</span></li>";
    } elseif (is_writable($dir)) {
        echo "<li>$dir: <span class='success'>✓ Writable</span></li>";
    } else {
        echo "<li>$dir: <span class='error'>✗ Not writable</span></li>";
    }
}
echo "</ul>";

// Test database connection
echo "<h2>🗄️ Database Connection Test</h2>";
require __DIR__ . "/config.php";

if ($db_available && $conn) {
    echo "<p class='success'>✅ Database connection successful!</p>";

    // Check if tables exist
    $result = $conn->query("SHOW TABLES LIKE 'photos'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ Photos table exists</p>";

        // Count photos
        $count_result = $conn->query("SELECT COUNT(*) as total FROM photos");
        $count = $count_result->fetch_assoc()['total'];
        echo "<p class='info'>📊 Total photos in database: $count</p>";
    } else {
        echo "<p class='error'>❌ Photos table not found. Please run setup.sql</p>";
    }
} else {
    echo "<p class='error'>❌ Database connection failed. Check config.php settings.</p>";
}

// Check if images exist
echo "<h2>🖼️ Image Files Check</h2>";
$image_count = count(glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE));
$thumb_count = count(glob("uploads/thumbs/*.{jpg,jpeg,png,gif}", GLOB_BRACE));

echo "<p><strong>Original Images:</strong> $image_count files</p>";
echo "<p><strong>Thumbnails:</strong> $thumb_count files</p>";

if ($image_count > 0 && $thumb_count == 0) {
    echo "<p class='info'>💡 <a href='regenerate_thumbs.php'>Click here to generate thumbnails</a></p>";
}

echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Upload Images:</strong> Place your photos in the <code>uploads/</code> folder</li>";
echo "<li><strong>Generate Thumbnails:</strong> Visit <code>regenerate_thumbs.php</code></li>";
echo "<li><strong>Test Gallery:</strong> Go to your main domain to see the gallery</li>";
echo "<li><strong>Customize:</strong> Edit <code>config.php</code> and <code>index.php</code> as needed</li>";
echo "</ol>";

echo "<p><a href='/' class='info'>← Back to Gallery</a></p>";
?>