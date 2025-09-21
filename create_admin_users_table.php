<?php
// create_admin_users_table.php - Create admin users table
require __DIR__ . "/config.php";

if (!DB_AVAILABLE) {
    die("Database not available. Cannot create admin users table.\n");
}

echo "Creating admin_users table...\n";

// Check if table already exists
$result = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($result->num_rows > 0) {
    echo "admin_users table already exists.\n";

    // Check if we need to add default admin
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_users");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        echo "No admin users found. Creating default admin user...\n";
        $defaultUsername = "Khanh";
        $defaultPassword = password_hash("0799102011", PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $defaultUsername, $defaultPassword);
        if ($stmt->execute()) {
            echo "Default admin user created: Khanh\n";
        } else {
            echo "Error creating default admin: " . $conn->error . "\n";
        }
        $stmt->close();
    } else {
        echo "Admin users already exist.\n";
    }
    exit;
}

// Create admin_users table
$sql = "CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
)";

if ($conn->query($sql) === TRUE) {
    echo "admin_users table created successfully.\n";

    // Create default admin user (store password as plain text)
    $defaultUsername = "Khanh";
    $defaultPassword = "0799102011";
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $defaultUsername, $defaultPassword);
    if ($stmt->execute()) {
        echo "Default admin user created: Khanh\n";
    } else {
        echo "Error creating default admin: " . $conn->error . "\n";
    }
    $stmt->close();

} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "Admin users system setup complete!\n";
?>