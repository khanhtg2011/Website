<?php
// test_thumbnail_display.php - Test thumbnail display issues
echo "<h1>Thumbnail Display Test</h1>";

// Check if uploads directory exists and is readable
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

echo "<h2>Directory Checks</h2>";
echo "<ul>";
echo "<li>Uploads directory exists: " . (is_dir($uploadDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Thumbs directory exists: " . (is_dir($thumbDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Uploads directory readable: " . (is_readable($uploadDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Thumbs directory readable: " . (is_readable($thumbDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "</ul>";

// Find all image files
$imageFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageFiles[] = $file;
        }
    }
}

echo "<h2>Image Files Found: " . count($imageFiles) . "</h2>";
if (count($imageFiles) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Filename</th><th>Full Size Exists</th><th>Thumbnail Exists</th><th>Full Size Size</th><th>Thumbnail Size</th><th>Permissions</th><th>Test Link</th></tr>";

    foreach ($imageFiles as $file) {
        $fullPath = $uploadDir . $file;
        $thumbPath = $thumbDir . $file;

        $fullExists = file_exists($fullPath);
        $thumbExists = file_exists($thumbPath);

        $fullSize = $fullExists ? filesize($fullPath) : 0;
        $thumbSize = $thumbExists ? filesize($thumbPath) : 0;

        $fullPerms = $fullExists ? substr(sprintf('%o', fileperms($fullPath)), -4) : 'N/A';
        $thumbPerms = $thumbExists ? substr(sprintf('%o', fileperms($thumbPath)), -4) : 'N/A';

        echo "<tr>";
        echo "<td>$file</td>";
        echo "<td style='color:" . ($fullExists ? 'green' : 'red') . "'>" . ($fullExists ? 'YES' : 'NO') . "</td>";
        echo "<td style='color:" . ($thumbExists ? 'green' : 'red') . "'>" . ($thumbExists ? 'YES' : 'NO') . "</td>";
        echo "<td>" . ($fullSize > 0 ? number_format($fullSize) . ' bytes' : 'N/A') . "</td>";
        echo "<td>" . ($thumbSize > 0 ? number_format($thumbSize) . ' bytes' : 'N/A') . "</td>";
        echo "<td>Full: $fullPerms | Thumb: $thumbPerms</td>";
        echo "<td>";
        if ($thumbExists) {
            echo "<a href='uploads/thumbs/$file' target='_blank'>View Thumbnail</a>";
        } else {
            echo "No thumbnail";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No image files found in uploads directory.</p>";
    echo "<p>This could mean:</p>";
    echo "<ul>";
    echo "<li>No images have been uploaded yet</li>";
    echo "<li>Images are stored in a different location</li>";
    echo "<li>Database connection issues preventing file detection</li>";
    echo "</ul>";
}

// Test a sample thumbnail if any exist
if (count($imageFiles) > 0) {
    echo "<h2>Sample Thumbnail Test</h2>";
    if (count($imageFiles) > 0) {
        $sampleFile = $imageFiles[0];
        $sampleThumb = "uploads/thumbs/" . $sampleFile;
        $sampleThumbPath = __DIR__ . "/" . $sampleThumb;
    
        echo "<p>Testing thumbnail: <strong>$sampleThumb</strong></p>";
        echo "<p>Full server path: <code>$sampleThumbPath</code></p>";
    
        if (file_exists($sampleThumbPath)) {
            $fileSize = filesize($sampleThumbPath);
            echo "<p>File size: <strong>$fileSize bytes</strong></p>";
    
            if ($fileSize == 0) {
                echo "<p style='color: red; font-weight: bold;'>❌ THUMBNAIL IS EMPTY (0 bytes) - This is why it appears white!</p>";
            } elseif ($fileSize < 100) {
                echo "<p style='color: orange; font-weight: bold;'>⚠️ THUMBNAIL IS VERY SMALL ($fileSize bytes) - Might be corrupted</p>";
            } else {
                echo "<p style='color: green;'>✅ Thumbnail file size looks normal</p>";
            }
    
            // Test image dimensions
            $imageInfo = getimagesize($sampleThumbPath);
            if ($imageInfo) {
                echo "<p>Image dimensions: {$imageInfo[0]} x {$imageInfo[1]} pixels</p>";
                echo "<p>Image type: {$imageInfo['mime']}</p>";
            } else {
                echo "<p style='color: red;'>❌ Cannot read image dimensions - file might be corrupted</p>";
            }
    
            echo "<img src='$sampleThumb' style='max-width: 200px; border: 1px solid #ccc;' alt='Test thumbnail' onerror=\"this.style.border='2px solid red'; this.alt='FAILED TO LOAD'\">";
            echo "<br><small>If the image above shows a red border or 'FAILED TO LOAD', there's a display issue.</small>";
        } else {
            echo "<p style='color: red;'>❌ Thumbnail file does not exist on server</p>";
        }
    } else {
        echo "<p>No image files to test</p>";
    }
}

echo "<h2>Server Information</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Current Directory: " . __DIR__ . "</li>";
echo "</ul>";

echo "<h2>Recommendations</h2>";
if (!is_dir($uploadDir)) {
    echo "<p style='color: red;'>❌ Create the uploads directory: <code>mkdir uploads</code></p>";
}
if (!is_dir($thumbDir)) {
    echo "<p style='color: red;'>❌ Create the thumbs directory: <code>mkdir uploads/thumbs</code></p>";
}
if (!is_readable($uploadDir)) {
    echo "<p style='color: red;'>❌ Fix uploads directory permissions: <code>chmod 755 uploads</code></p>";
}
if (!is_readable($thumbDir)) {
    echo "<p style='color: red;'>❌ Fix thumbs directory permissions: <code>chmod 755 uploads/thumbs</code></p>";
}
if (count($imageFiles) == 0) {
    echo "<p style='color: orange;'>⚠️ No images found. Upload some images first.</p>";
}
?>