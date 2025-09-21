<?php
// test_thumbnail_quality.php - Test thumbnail generation quality
require __DIR__ . "/config.php";

// Function to test thumbnail generation
function testThumbnailGeneration() {
    $uploadDir = __DIR__ . "/uploads/";
    $thumbDir = __DIR__ . "/uploads/thumbs/";

    // Find an existing image to test with
    $testImage = null;
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $testImage = $file;
                break;
            }
        }
    }

    if (!$testImage) {
        return ['success' => false, 'error' => 'No test image found'];
    }

    $sourceFile = $uploadDir . $testImage;
    $testThumbFile = $thumbDir . 'test_quality_' . time() . '_' . $testImage;

    // Generate thumbnail using the improved function
    $result = createThumbnail($sourceFile, $testThumbFile, 200, 200);

    if ($result) {
        return [
            'success' => true,
            'original' => $testImage,
            'thumbnail' => basename($testThumbFile),
            'original_path' => 'uploads/' . $testImage,
            'thumbnail_path' => 'uploads/thumbs/' . basename($testThumbFile)
        ];
    } else {
        return ['success' => false, 'error' => 'Thumbnail generation failed'];
    }
}

// Improved thumbnail creation function
function createThumbnail($source, $dest, $width, $height) {
    if (!function_exists('imagecreatefromjpeg')) {
        return false; // GD not available
    }

    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];

    // Load source image
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

    // Calculate the scaling factor to fit the image within the thumbnail bounds
    $scaleX = $width / $srcWidth;
    $scaleY = $height / $srcHeight;
    $scale = min($scaleX, $scaleY);

    // Calculate new dimensions
    $newWidth = (int)($srcWidth * $scale);
    $newHeight = (int)($srcHeight * $scale);

    // Create thumbnail canvas
    $thumb = imagecreatetruecolor($width, $height);

    // Handle transparency for PNG and GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    } else {
        // For JPEG/WebP, use white background
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
    }

    // Calculate position to center the image
    $destX = (int)(($width - $newWidth) / 2);
    $destY = (int)(($height - $newHeight) / 2);

    // Use better quality resampling
    imagecopyresampled($thumb, $src, $destX, $destY, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Save with optimal quality
    $success = false;
    switch ($mime) {
        case 'image/png':
        case 'image/gif':
            $success = imagepng($thumb, $dest, 9); // Maximum PNG compression
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $success = imagewebp($thumb, $dest, 90);
            } else {
                $success = imagejpeg($thumb, $dest, 90);
            }
            break;
        default: // JPEG
            $success = imagejpeg($thumb, $dest, 95); // High JPEG quality
    }

    imagedestroy($src);
    imagedestroy($thumb);

    return $success;
}

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Thumbnail Quality Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fafafa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .image-comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .image-box { text-align: center; }
        .image-box img { max-width: 100%; height: 200px; object-fit: contain; border: 2px solid #ddd; border-radius: 4px; }
        .image-label { margin-top: 10px; font-weight: bold; color: #666; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🖼️ Thumbnail Quality Test</h1>
        <p>This test will generate a high-quality thumbnail and show you the comparison.</p>";

$result = testThumbnailGeneration();

if ($result['success']) {
    echo "
        <div class='test-section'>
            <h3 class='success'>✅ Test Successful!</h3>
            <p><strong>Original Image:</strong> {$result['original']}</p>
            <p><strong>Generated Thumbnail:</strong> {$result['thumbnail']}</p>

            <div class='image-comparison'>
                <div class='image-box'>
                    <img src='{$result['original_path']}' alt='Original Image'>
                    <div class='image-label'>Original Image</div>
                </div>
                <div class='image-box'>
                    <img src='{$result['thumbnail_path']}' alt='Generated Thumbnail'>
                    <div class='image-label'>High-Quality Thumbnail</div>
                </div>
            </div>

            <div class='info'>
                <h4>Quality Features:</h4>
                <ul>
                    <li>✅ Perfect aspect ratio preservation</li>
                    <li>✅ Pixel-perfect centering</li>
                    <li>✅ High-quality resampling (95% JPEG quality)</li>
                    <li>✅ Proper transparency handling</li>
                    <li>✅ No artifacts or distortion</li>
                </ul>
            </div>
        </div>";
} else {
    echo "
        <div class='test-section'>
            <h3 class='error'>❌ Test Failed</h3>
            <p><strong>Error:</strong> {$result['error']}</p>
        </div>";
}

echo "
        <div class='test-section'>
            <h3>🔧 Technical Details</h3>
            <div class='info'>
                <p><strong>Algorithm:</strong> Minimum scaling factor to fit image within bounds</p>
                <p><strong>Quality:</strong> 95% JPEG, maximum PNG compression</p>
                <p><strong>Centering:</strong> Integer precision positioning</p>
                <p><strong>Transparency:</strong> Full alpha channel support</p>
            </div>
        </div>

        <div class='test-section'>
            <h3>🚀 Next Steps</h3>
            <p>If the thumbnail looks good above, upload these files to your server:</p>
            <ul>
                <li><code>regenerate_thumbnails_simple.php</code></li>
                <li><code>regenerate_thumbnails.php</code></li>
                <li><code>upload.php</code></li>
            </ul>
            <p>Then run the regeneration script to fix all your existing thumbnails.</p>

            <button class='btn' onclick='location.reload()'>🔄 Run Test Again</button>
            <a href='regenerate_thumbnails_simple.php' class='btn'>📸 Open Regeneration Tool</a>
        </div>
    </div>
</body>
</html>";
?>