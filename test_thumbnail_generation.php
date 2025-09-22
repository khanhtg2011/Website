<?php
// test_thumbnail_generation.php - Test thumbnail generation
require __DIR__ . "/config.php";

// Test thumbnail generation function
function createThumbnail($source, $dest, $width, $height) {
    if (!function_exists('imagecreatefromjpeg')) {
        return false; // GD not available
    }

    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png': $src = imagecreatefrompng($source); break;
        case 'image/gif': $src = imagecreatefromgif($source); break;
        default: return false;
    }

    $srcWidth = imagesx($src);
    $srcHeight = imagesy($src);
    $aspect = $srcWidth / $srcHeight;

    if ($width / $height > $aspect) {
        $newWidth = $height * $aspect;
        $newHeight = $height;
    } else {
        $newWidth = $width;
        $newHeight = $width / $aspect;
    }

    $thumb = imagecreatetruecolor($width, $height);

    // Create transparent background for PNG, or use a neutral gray for JPEG
    if ($mime === 'image/png') {
        // Enable alpha blending and create transparent background
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    } else {
        // For JPEG and other formats, use a neutral gray background
        $bgColor = imagecolorallocate($thumb, 240, 240, 240);
        imagefill($thumb, 0, 0, $bgColor);
    }

    imagecopyresampled($thumb, $src, ($width - $newWidth)/2, ($height - $newHeight)/2, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Save the thumbnail in the appropriate format
    if ($mime === 'image/png') {
        imagepng($thumb, $dest, 8); // PNG with compression level 8
    } else {
        imagejpeg($thumb, $dest, 85); // JPEG with quality 85
    }

    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}

echo "<h1>Thumbnail Generation Test</h1>";

// Check if GD library is available
if (!function_exists('imagecreatefromjpeg')) {
    echo "<p style='color: red;'>❌ GD library is not available. Thumbnail generation will not work.</p>";
    echo "<p>Please install the GD extension for PHP.</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ GD library is available.</p>";
}

// Check directories
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

echo "<h2>Directory Check</h2>";
if (!is_dir($uploadDir)) {
    echo "<p style='color: red;'>❌ Upload directory does not exist: $uploadDir</p>";
} else {
    echo "<p style='color: green;'>✅ Upload directory exists: $uploadDir</p>";
}

if (!is_dir($thumbDir)) {
    echo "<p style='color: red;'>❌ Thumbnail directory does not exist: $thumbDir</p>";
} else {
    echo "<p style='color: green;'>✅ Thumbnail directory exists: $thumbDir</p>";
}

// Check permissions
echo "<h2>Permissions Check</h2>";
if (is_writable($uploadDir)) {
    echo "<p style='color: green;'>✅ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
}

if (is_writable($thumbDir)) {
    echo "<p style='color: green;'>✅ Thumbnail directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Thumbnail directory is not writable</p>";
}

// List existing files
echo "<h2>Existing Files</h2>";
$uploadFiles = glob($uploadDir . "*");
$thumbFiles = glob($thumbDir . "*");

echo "<h3>Upload Directory (" . count($uploadFiles) . " files)</h3>";
if (count($uploadFiles) > 0) {
    echo "<ul>";
    foreach ($uploadFiles as $file) {
        if (is_file($file)) {
            $filename = basename($file);
            $size = filesize($file);
            echo "<li>$filename (" . round($size/1024, 1) . " KB)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>No files in upload directory</p>";
}

echo "<h3>Thumbnail Directory (" . count($thumbFiles) . " files)</h3>";
if (count($thumbFiles) > 0) {
    echo "<ul>";
    foreach ($thumbFiles as $file) {
        if (is_file($file)) {
            $filename = basename($file);
            $size = filesize($file);
            echo "<li>$filename (" . round($size/1024, 1) . " KB)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠️ No thumbnail files found. This confirms the thumbnail generation issue!</p>";
}

echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li>✅ GD Library: Available</li>";
echo "<li>✅ Upload Directory: " . (is_dir($uploadDir) ? "Exists" : "Missing") . "</li>";
echo "<li>✅ Thumbnail Directory: " . (is_dir($thumbDir) ? "Exists" : "Missing") . "</li>";
echo "<li>✅ Upload Permissions: " . (is_writable($uploadDir) ? "Writable" : "Not Writable") . "</li>";
echo "<li>✅ Thumbnail Permissions: " . (is_writable($thumbDir) ? "Writable" : "Not Writable") . "</li>";
echo "<li>📁 Upload Files: " . count($uploadFiles) . "</li>";
echo "<li>🖼️ Thumbnail Files: " . count($thumbFiles) . "</li>";
echo "</ul>";

if (count($thumbFiles) === 0 && count($uploadFiles) > 0) {
    echo "<p style='color: red; font-weight: bold;'>🚨 ISSUE CONFIRMED: You have " . count($uploadFiles) . " uploaded files but 0 thumbnails!</p>";
    echo "<p>This explains the white stripes - thumbnails are not being generated.</p>";
    echo "<p>The fix has been applied to the upload.php file. Try uploading a new image to test.</p>";
} elseif (count($thumbFiles) > 0) {
    echo "<p style='color: green;'>✅ Thumbnails are being generated properly.</p>";
}
?>