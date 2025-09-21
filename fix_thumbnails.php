<?php
// fix_thumbnails.php - Automatic thumbnail fix script
echo "<h1>Thumbnail Fix Script</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;}</style>";

$issues = [];
$fixes = [];

// Check directories
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

echo "<h2>Step 1: Checking Directories</h2>";

// Create uploads directory if missing
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        $fixes[] = "Created uploads directory";
        echo "<p class='success'>✅ Created uploads directory</p>";
    } else {
        $issues[] = "Failed to create uploads directory";
        echo "<p class='error'>❌ Failed to create uploads directory</p>";
    }
} else {
    echo "<p class='success'>✅ Uploads directory exists</p>";
}

// Create thumbs directory if missing
if (!is_dir($thumbDir)) {
    if (mkdir($thumbDir, 0755, true)) {
        $fixes[] = "Created thumbs directory";
        echo "<p class='success'>✅ Created thumbs directory</p>";
    } else {
        $issues[] = "Failed to create thumbs directory";
        echo "<p class='error'>❌ Failed to create thumbs directory</p>";
    }
} else {
    echo "<p class='success'>✅ Thumbs directory exists</p>";
}

// Check permissions
echo "<h2>Step 2: Checking Permissions</h2>";

if (is_dir($uploadDir)) {
    $currentPerms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    echo "<p>Uploads directory permissions: $currentPerms</p>";
    if ($currentPerms !== '0755') {
        if (chmod($uploadDir, 0755)) {
            $fixes[] = "Fixed uploads directory permissions to 755";
            echo "<p class='success'>✅ Fixed uploads directory permissions</p>";
        } else {
            $issues[] = "Failed to fix uploads directory permissions";
            echo "<p class='error'>❌ Failed to fix uploads directory permissions</p>";
        }
    }
}

if (is_dir($thumbDir)) {
    $currentPerms = substr(sprintf('%o', fileperms($thumbDir)), -4);
    echo "<p>Thumbs directory permissions: $currentPerms</p>";
    if ($currentPerms !== '0755') {
        if (chmod($thumbDir, 0755)) {
            $fixes[] = "Fixed thumbs directory permissions to 755";
            echo "<p class='success'>✅ Fixed thumbs directory permissions</p>";
        } else {
            $issues[] = "Failed to fix thumbs directory permissions";
            echo "<p class='error'>❌ Failed to fix thumbs directory permissions</p>";
        }
    }
}

// Check for empty/corrupted thumbnails
echo "<h2>Step 3: Checking Thumbnail Files</h2>";

if (is_dir($thumbDir)) {
    $thumbFiles = glob($thumbDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    echo "<p>Found " . count($thumbFiles) . " thumbnail files</p>";

    $emptyFiles = [];
    $smallFiles = [];

    foreach ($thumbFiles as $file) {
        $size = filesize($file);
        if ($size == 0) {
            $emptyFiles[] = basename($file);
        } elseif ($size < 100) {
            $smallFiles[] = basename($file) . " ($size bytes)";
        }
    }

    if (!empty($emptyFiles)) {
        echo "<p class='error'>❌ Found " . count($emptyFiles) . " empty thumbnail files:</p>";
        echo "<ul>";
        foreach ($emptyFiles as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
        $issues[] = "Empty thumbnail files found";
    }

    if (!empty($smallFiles)) {
        echo "<p class='warning'>⚠️ Found " . count($smallFiles) . " very small thumbnail files (possibly corrupted):</p>";
        echo "<ul>";
        foreach ($smallFiles as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    }
}

// Test gallery access
echo "<h2>Step 4: Testing Gallery Access</h2>";

$galleryTest = @file_get_contents('list.php', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'X-Requested-With: XMLHttpRequest'
    ]
]));

if ($galleryTest === false) {
    echo "<p class='error'>❌ Cannot access gallery data</p>";
    $issues[] = "Gallery data not accessible";
} else {
    $galleryData = json_decode($galleryTest, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p class='error'>❌ Gallery returned invalid data</p>";
        $issues[] = "Gallery returns invalid JSON";
    } else {
        $photoCount = count($galleryData);
        echo "<p class='success'>✅ Gallery accessible, found $photoCount photos</p>";
    }
}

// Summary
echo "<h2>Step 5: Summary</h2>";

if (empty($issues)) {
    echo "<p class='success'>✅ All automatic fixes completed successfully!</p>";
} else {
    echo "<p class='warning'>⚠️ Some issues could not be fixed automatically:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

if (!empty($fixes)) {
    echo "<p class='success'>✅ Applied " . count($fixes) . " automatic fixes:</p>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
if (!empty($issues)) {
    echo "<li><strong>Manual fixes needed:</strong> Address the issues listed above</li>";
}
echo "<li><strong>Test the gallery:</strong> Visit your gallery page and check if thumbnails load</li>";
echo "<li><strong>Clear browser cache:</strong> Hard refresh (Ctrl+F5) to see changes</li>";
if (count($thumbFiles) == 0 || !empty($emptyFiles)) {
    echo "<li><strong>Regenerate thumbnails:</strong> Run <code>regenerate_thumbnails.php</code> if needed</li>";
}
echo "<li><strong>Check detailed diagnostics:</strong> Run <code>debug_thumbnails.php</code> for more info</li>";
echo "</ol>";

echo "<p><a href='debug_thumbnails.php'>Run Detailed Diagnostics</a> | <a href='test_thumbnail_display.php'>Test Thumbnail Display</a> | <a href='regenerate_thumbnails.php'>Regenerate Thumbnails</a></p>";
?>