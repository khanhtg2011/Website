<?php
// thumbnail_test.php - Simple test to check thumbnail functionality
echo "<h1>Thumbnail Test</h1>";

// Check if GD is available
echo "<h2>GD Extension Check</h2>";
echo "<p>GD Extension Available: " . (extension_loaded('gd') ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</p>";

// Check directories
echo "<h2>Directory Check</h2>";
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

echo "<ul>";
echo "<li>Uploads directory exists: " . (is_dir($uploadDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Thumbs directory exists: " . (is_dir($thumbDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Uploads directory writable: " . (is_writable($uploadDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "<li>Thumbs directory writable: " . (is_writable($thumbDir) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</li>";
echo "</ul>";

// Check for image files
echo "<h2>Image Files Check</h2>";
$imageFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'thumbs') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageFiles[] = $file;
        }
    }
}

echo "<p>Found " . count($imageFiles) . " image files</p>";

if (count($imageFiles) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Filename</th><th>Thumbnail Exists</th><th>Thumbnail Size</th><th>Test Link</th></tr>";

    foreach ($imageFiles as $file) {
        $thumbPath = $thumbDir . $file;
        $thumbWebPath = "uploads/thumbs/" . $file;

        echo "<tr>";
        echo "<td>$file</td>";
        echo "<td>" . (file_exists($thumbPath) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "</td>";
        echo "<td>" . (file_exists($thumbPath) ? filesize($thumbPath) . " bytes" : "N/A") . "</td>";
        echo "<td>";
        if (file_exists($thumbPath)) {
            echo "<a href='$thumbWebPath' target='_blank'>View</a>";
        } else {
            echo "No thumbnail";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test thumbnail generation
echo "<h2>Thumbnail Generation Test</h2>";
if (count($imageFiles) > 0 && extension_loaded('gd')) {
    $testFile = $uploadDir . $imageFiles[0];
    $testThumb = $thumbDir . $imageFiles[0];

    echo "<p>Testing thumbnail generation for: " . $imageFiles[0] . "</p>";

    if (!file_exists($testThumb)) {
        echo "<p>Generating thumbnail...</p>";
        if (createThumbnail($testFile, $testThumb, 200, 200)) {
            echo "<p style='color:green'>✅ Thumbnail generated successfully!</p>";
            echo "<img src='uploads/thumbs/" . $imageFiles[0] . "' style='max-width: 200px; border: 1px solid #ccc;'>";
        } else {
            echo "<p style='color:red'>❌ Thumbnail generation failed!</p>";
        }
    } else {
        echo "<p>Thumbnail already exists</p>";
        echo "<img src='uploads/thumbs/" . $imageFiles[0] . "' style='max-width: 200px; border: 1px solid #ccc;'>";
    }
}

function createThumbnail($source, $dest, $width, $height) {
    if (!function_exists('imagecreatefromjpeg')) {
        return false;
    }

    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png': $src = imagecreatefrompng($source); break;
        case 'image/gif': $src = imagecreatefromgif($source); break;
        case 'image/webp': $src = imagecreatefromwebp($source); break;
        default: return false;
    }

    if (!$src) return false;

    $srcWidth = imagesx($src);
    $srcHeight = imagesy($src);

    $scale = max($width / $srcWidth, $height / $srcHeight);
    $newWidth = (int)($srcWidth * $scale);
    $newHeight = (int)($srcHeight * $scale);

    $thumb = imagecreatetruecolor($width, $height);

    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    }

    $destX = (int)(($width - $newWidth) / 2);
    $destY = (int)(($height - $newHeight) / 2);

    imagecopyresampled($thumb, $src, $destX, $destY, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    $success = false;
    switch ($mime) {
        case 'image/png':
        case 'image/gif':
            $success = imagepng($thumb, $dest, 9);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $success = imagewebp($thumb, $dest, 90);
            } else {
                $success = imagejpeg($thumb, $dest, 90);
            }
            break;
        default:
            $success = imagejpeg($thumb, $dest, 90);
    }

    imagedestroy($src);
    imagedestroy($thumb);

    return $success;
}
?>