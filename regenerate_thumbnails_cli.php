<?php
// regenerate_thumbnails_cli.php - Command-line thumbnail regeneration
require __DIR__ . "/config.php";
require __DIR__ . "/cache_utils.php";

echo "🔄 Thumbnail Regeneration Script\n";
echo "================================\n\n";

// Check GD library
if (!function_exists('imagecreatefromjpeg')) {
    echo "❌ ERROR: GD library is not available. Cannot generate thumbnails.\n";
    echo "Please install the GD extension for PHP.\n";
    exit(1);
}

echo "✅ GD library is available\n";

// Check directories
$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

if (!is_dir($uploadDir)) {
    echo "❌ ERROR: Upload directory not found: $uploadDir\n";
    exit(1);
}

if (!is_dir($thumbDir)) {
    echo "📁 Creating thumbnail directory: $thumbDir\n";
    mkdir($thumbDir, 0755, true);
}

echo "✅ Directories are ready\n\n";

// Scan for images
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$images = [];
$missingThumbs = 0;

$files = scandir($uploadDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $imageExtensions)) {
        $images[] = $file;

        // Check if thumbnail exists
        $thumbFile = $thumbDir . $file;
        if (!file_exists($thumbFile)) {
            $missingThumbs++;
        }
    }
}

$totalImages = count($images);
echo "📊 Scan Results:\n";
echo "   Total images: $totalImages\n";
echo "   Missing thumbnails: $missingThumbs\n\n";

if ($missingThumbs === 0) {
    echo "✅ All thumbnails are up to date!\n";
    exit(0);
}

// Process thumbnails
echo "🔄 Starting thumbnail regeneration...\n\n";

$successful = 0;
$failed = 0;

foreach ($images as $index => $filename) {
    $thumbFile = $thumbDir . $filename;
    $sourceFile = $uploadDir . $filename;

    // Skip if thumbnail already exists
    if (file_exists($thumbFile)) {
        echo "⏭️  Skipping: $filename (thumbnail exists)\n";
        continue;
    }

    echo "📷 Processing: $filename... ";

    if (createThumbnail($sourceFile, $thumbFile, 200, 200)) {
        echo "✅ Success\n";
        $successful++;
    } else {
        echo "❌ Failed\n";
        $failed++;
    }

    // Progress indicator
    $progress = round((($index + 1) / $totalImages) * 100);
    echo "   Progress: $progress% (" . ($index + 1) . "/$totalImages)\n";
}

echo "\n🎉 Regeneration Complete!\n";
echo "========================\n";
echo "✅ Successful: $successful\n";
echo "❌ Failed: $failed\n";
echo "📊 Success Rate: " . round(($successful / $missingThumbs) * 100) . "%\n\n";

// Clear gallery cache
echo "🧹 Clearing gallery cache...\n";
clearGalleryCache();
echo "✅ Cache cleared\n\n";

echo "🎊 Thumbnail regeneration finished!\n";

// Improved thumbnail creation function
function createThumbnail($source, $dest, $width, $height) {
    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png': $src = imagecreatefrompng($source); break;
        case 'image/gif': $src = imagecreatefromgif($source); break;
        default: return false;
    }

    if (!$src) return false;

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
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    } else {
        $bgColor = imagecolorallocate($thumb, 240, 240, 240);
        imagefill($thumb, 0, 0, $bgColor);
    }

    imagecopyresampled($thumb, $src, ($width - $newWidth)/2, ($height - $newHeight)/2, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Save the thumbnail in the appropriate format
    $success = false;
    if ($mime === 'image/png') {
        $success = imagepng($thumb, $dest, 8);
    } else {
        $success = imagejpeg($thumb, $dest, 85);
    }

    imagedestroy($src);
    imagedestroy($thumb);

    return $success;
}
?>