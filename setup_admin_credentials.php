<?php
// setup_admin_credentials.php - Setup admin credentials table
require __DIR__ . "/config.php";

echo "<h1>Admin Credentials Database Setup</h1>";

// Check database availability
if (!DB_AVAILABLE || !$db) {
    die("❌ Database not available. Please check your database configuration.");
} else {
    echo "✅ Database connected successfully (" . DB_TYPE . ")<br>";
}

// Create admin_credentials table
echo "<h2>Creating Admin Credentials Table...</h2>";
if (DB_TYPE === 'sqlite') {
    $sql = "CREATE TABLE IF NOT EXISTS admin_credentials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
} else {
    $sql = "CREATE TABLE IF NOT EXISTS admin_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
}

try {
    $db->query($sql);
    echo "✅ Admin credentials table created successfully<br>";
} catch (Exception $e) {
    echo "❌ Error creating admin credentials table: " . $e->getMessage() . "<br>";
}

// Insert default admin credentials
echo "<h2>Setting up Default Admin Account...</h2>";
$default_username = "Khanh";
$default_password = "0799102011";

$sql = "INSERT IGNORE INTO admin_credentials (username, password) VALUES (?, ?)";
$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $default_username, $default_password);

try {
    $stmt->execute();
    echo "✅ Default admin account created (username: $default_username)<br>";
} catch (Exception $e) {
    echo "❌ Error creating default admin account: " . $e->getMessage() . "<br>";
}
$stmt->close();

// Test the setup
echo "<h2>Testing Setup...</h2>";
try {
    $result = $db->query("SELECT COUNT(*) as count FROM admin_credentials");
    if (DB_TYPE === 'sqlite') {
        $row = $result->fetch(PDO::FETCH_ASSOC);
    } else {
        $row = $result->fetch_assoc();
    }
    echo "✅ Admin credentials table has " . $row['count'] . " accounts<br>";
} catch (Exception $e) {
    echo "❌ Error testing setup: " . $e->getMessage() . "<br>";
}

echo "<h2>🎉 Setup Complete!</h2>";
echo "<p>Your admin login system now uses database storage!</p>";
echo "<p><a href='/'>← Back to Gallery</a></p>";
?>