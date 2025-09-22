<?php
// test_border_fix.php - Test the border fix for thumbnails
require __DIR__ . "/config.php";

// Create a simple test image to verify the border fix
function createTestThumbnail() {
    // Create a small test image (100x100)
    $width = 100;
    $height = 100;

    // Create source image (red square)
    $src = imagecreatetruecolor(80, 80);
    $red = imagecolorallocate($src, 255, 0, 0);
    imagefill($src, 0, 0, $red);

    $srcWidth = imagesx($src);
    $srcHeight = imagesy($src);

    // Calculate aspect ratios
    $srcAspect = $srcWidth / $srcHeight;
    $destAspect = $width / $height;

    // Calculate dimensions to fit the image
    if ($srcAspect > $destAspect) {
        $newWidth = $width;
        $newHeight = $width / $srcAspect;
    } else {
        $newHeight = $height;
        $newWidth = $height * $srcAspect;
    }

    // Create the thumbnail canvas
    $thumb = imagecreatetruecolor($width, $height);

    // Fill background to match gallery background (fafafa = 250, 250, 250)
    $bgColor = imagecolorallocate($thumb, 250, 250, 250); // Match gallery background
    imagefill($thumb, 0, 0, $bgColor);

    // Center the image on the canvas
    $destX = ($width - $newWidth) / 2;
    $destY = ($height - $newHeight) / 2;

    // Copy the source image onto the thumbnail
    imagecopyresampled($thumb, $src, $destX, $destY, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Save the thumbnail
    $outputFile = __DIR__ . "/uploads/thumbs/test_border_fix.jpg";
    imagejpeg($thumb, $outputFile, 90);

    // Clean up
    imagedestroy($src);
    imagedestroy($thumb);

    return $outputFile;
}

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Border Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fafafa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-image { border: 2px solid #ddd; margin: 10px; display: inline-block; }
        .test-image img { display: block; }
        .info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🖼️ Thumbnail Border Fix Test</h1>

        <div class='info'>
            <h3>Background Color Test</h3>
            <p><strong>Gallery Background:</strong> #fafafa (250, 250, 250)</p>
            <p><strong>Thumbnail Background:</strong> #fafafa (250, 250, 250)</p>
            <p><strong>Expected Result:</strong> No visible border around thumbnails</p>
        </div>";

if (function_exists('imagecreatetruecolor')) {
    $testFile = createTestThumbnail();
    echo "
        <h3>Test Thumbnail Generated:</h3>
        <div class='test-image'>
            <img src='uploads/thumbs/test_border_fix.jpg' alt='Test Thumbnail' style='width: 100px; height: 100px;'>
        </div>
        <p><em>If you can see a red square centered on a light background with no visible border, the fix is working!</em></p>";
} else {
    echo "<p style='color: red;'>❌ GD library not available - cannot test thumbnail generation</p>";
}

echo "
        <h3>How the Fix Works:</h3>
        <ul>
            <li>✅ Gallery background: <code>#fafafa</code> (250, 250, 250)</li>
            <li>✅ Thumbnail background: <code>#fafafa</code> (250, 250, 250)</li>
            <li>✅ Perfect color match = No visible border</li>
            <li>✅ PNG transparency preserved when needed</li>
        </ul>

        <h3>Files Updated:</h3>
        <ul>
            <li><code>regenerate_thumbnails_simple.php</code></li>
            <li><code>regenerate_thumbnails.php</code></li>
            <li><code>upload.php</code></li>
        </ul>

        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Upload these updated files to your server</li>
            <li>Run the thumbnail regeneration script</li>
            <li>Check that thumbnails no longer have visible borders</li>
            <li>Upload new images to test the fix for new thumbnails</li>
        </ol>
    </div>
</body>
</html>";
?>