<?php
// create_test_image.php - Create a test image for thumbnail testing

// Create a simple test image (400x300 gradient)
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Create a gradient background
for ($y = 0; $y < $height; $y++) {
    $r = (int)(255 * ($y / $height)); // Red increases from top to bottom
    $g = (int)(100 + 155 * ($y / $height)); // Green gradient
    $b = (int)(200 - 100 * ($y / $height)); // Blue decreases

    $color = imagecolorallocate($image, $r, $g, $b);
    imageline($image, 0, $y, $width - 1, $y, $color);
}

// Add some text
$textColor = imagecolorallocate($image, 255, 255, 255);
imagestring($image, 5, 20, 20, "Test Image", $textColor);
imagestring($image, 3, 20, 50, "400x300 Gradient", $textColor);
imagestring($image, 3, 20, 70, "For Thumbnail Testing", $textColor);

// Add a simple shape
$shapeColor = imagecolorallocate($image, 255, 0, 255);
imagefilledrectangle($image, 200, 150, 300, 250, $shapeColor);

// Save the test image
$testImagePath = __DIR__ . "/uploads/test_gradient.jpg";
imagejpeg($image, $testImagePath, 90);

imagedestroy($image);

echo "Test image created successfully at: $testImagePath\n";
echo "Image size: " . filesize($testImagePath) . " bytes\n";
?>