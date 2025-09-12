<?php
// create_sample_photo.php - Create a sample photo with EXIF data for testing

// Create a simple image with EXIF data
$width = 800;
$height = 600;
$image = imagecreatetruecolor($width, $height);

// Fill with gradient background
for ($y = 0; $y < $height; $y++) {
    // Calculate color values and clamp to 0-255 range
    $red = min(255, max(0, 100 + $y * 0.3));
    $green = min(255, max(0, 150 + $y * 0.2));
    $blue = min(255, max(0, 200 + $y * 0.1));

    $color = imagecolorallocate($image, $red, $green, $blue);
    imageline($image, 0, $y, $width, $y, $color);
}

// Add some text
$textColor = imagecolorallocate($image, 255, 255, 255);
imagestring($image, 5, 50, 50, "Sample Photo with EXIF Data", $textColor);
imagestring($image, 3, 50, 100, "Camera: Sony A7R IV", $textColor);
imagestring($image, 3, 50, 130, "Lens: Sony FE 135mm f/1.8 GM", $textColor);
imagestring($image, 3, 50, 160, "Settings: f/1.8, 135mm, ISO 100", $textColor);

// Save the image
$filename = "sample_exif_" . time() . ".jpg";
$filepath = __DIR__ . "/uploads/" . $filename;

// Ensure uploads directory exists
if (!is_dir(__DIR__ . "/uploads/")) {
    mkdir(__DIR__ . "/uploads/", 0755, true);
}

imagejpeg($image, $filepath, 90);
imagedestroy($image);

// Now add EXIF data manually with realistic values
$exifData = [
    'Make' => 'Sony',
    'Model' => 'ILCE-7', // Sony A7
    'FNumber' => '5.6', // Will display as f/5.6
    'FocalLength' => '50', // Will display as 50mm
    'ISOSpeedRatings' => '100',
    'ExposureTime' => '0.01', // 1/100s
    'DateTimeOriginal' => date('Y:m:d H:i:s'),
    'Lens' => 'Sony FE 50mm f/1.4 ZA'
];

// Create EXIF data structure
$metadata = [
    'IFD0' => [
        'Make' => $exifData['Make'],
        'Model' => $exifData['Model']
    ],
    'EXIF' => [
        'FNumber' => $exifData['FNumber'],
        'FocalLength' => $exifData['FocalLength'],
        'ISOSpeedRatings' => $exifData['ISOSpeedRatings'],
        'ExposureTime' => $exifData['ExposureTime'],
        'DateTimeOriginal' => $exifData['DateTimeOriginal']
    ],
    'filesize' => filesize($filepath),
    'uploaded_at' => time()
];

// Save metadata to JSON file
file_put_contents($filepath . ".json", json_encode($metadata));

// Save to database
require __DIR__ . "/config.php";

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    $uploader = $_SESSION['username'] ?? 'admin';
    $ip = $_SERVER['REMOTE_ADDR'];

    // Check if album_id column exists
    $columnExists = $conn->query("SHOW COLUMNS FROM photos LIKE 'album_id'")->num_rows > 0;

    if ($columnExists) {
        $stmt = $conn->prepare("INSERT INTO photos (filename, album_id, uploaded_at, uploader, ip_address) VALUES (?, 1, NOW(), ?, ?)");
        $stmt->bind_param("sss", $filename, $uploader, $ip);
    } else {
        $stmt = $conn->prepare("INSERT INTO photos (filename, uploaded_at, uploader, ip_address) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("sss", $filename, $uploader, $ip);
    }

    if ($stmt->execute()) {
        echo "✅ Sample photo created successfully!<br>";
        echo "📁 File: $filename<br>";
        echo "📷 Camera: Sony A7<br>";
        echo "🔍 Lens: Sony FE 50mm f/1.4 ZA<br>";
        echo "⚙️ Settings: f/5.6, 50mm, 1/100s, ISO 100<br>";
        echo "<br><a href='/'>← Back to Gallery</a>";
    } else {
        echo "❌ Database error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "❌ Please login as admin first to create sample photo.";
}

$conn->close();
?>