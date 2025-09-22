<?php
require __DIR__ . "/config.php";

// Create private_memories table
if (DB_AVAILABLE && $db) {
    try {
        // Create private_memories table
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS private_memories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                original_filename VARCHAR(255) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                uploader VARCHAR(100) DEFAULT 'admin',
                ip_address VARCHAR(45) DEFAULT '',
                file_size INT DEFAULT 0,
                mime_type VARCHAR(100) DEFAULT '',
                width INT DEFAULT 0,
                height INT DEFAULT 0,
                metadata TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        if ($db->query($createTableSQL)) {
            echo "✅ Private memories table created successfully!\n";
        } else {
            echo "❌ Failed to create private memories table: " . $db->error . "\n";
        }

        // Create indexes for better performance
        $indexSQL = "
            CREATE INDEX idx_private_memories_filename ON private_memories(filename);
            CREATE INDEX idx_private_memories_uploaded_at ON private_memories(uploaded_at);
        ";

        if ($db->multi_query($indexSQL)) {
            echo "✅ Indexes created for private memories table!\n";
        } else {
            echo "⚠️  Could not create indexes: " . $db->error . "\n";
        }

    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database not available. Please check your database configuration.\n";
}

// Create private_memories directory if it doesn't exist
$privateDir = __DIR__ . '/private_memories';
if (!is_dir($privateDir)) {
    if (mkdir($privateDir, 0755, true)) {
        echo "✅ Private memories directory created!\n";
    } else {
        echo "❌ Failed to create private memories directory!\n";
    }
} else {
    echo "✅ Private memories directory already exists!\n";
}

// Create subdirectories for organization
$subdirs = ['thumbs', 'originals'];
foreach ($subdirs as $subdir) {
    $subdirPath = $privateDir . '/' . $subdir;
    if (!is_dir($subdirPath)) {
        if (mkdir($subdirPath, 0755, true)) {
            echo "✅ Created subdirectory: $subdir\n";
        } else {
            echo "❌ Failed to create subdirectory: $subdir\n";
        }
    }
}

echo "\n🎉 Private memories storage setup complete!\n";
echo "📁 Directory: private_memories/\n";
echo "🗄️  Table: private_memories\n";
echo "🔒 Only accessible by admin users\n";
?>