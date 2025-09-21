<?php
// For testing purposes, we'll simulate the upload without connecting to the database
// In a real implementation, this would save the file and update the database

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo "No permission";
    exit;
}

// Check CSRF token
require __DIR__ . "/config.php";
require __DIR__ . "/cache_utils.php";
if (!isset($_POST['csrf']) || $_POST['csrf'] !== csrf_token()) {
    http_response_code(403);
    echo "CSRF error: " . (isset($_POST['csrf']) ? "token mismatch" : "no token");
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo "No file";
    exit;
}

$uploadDir = __DIR__ . "/uploads/";
$thumbDir = __DIR__ . "/uploads/thumbs/";

// Ensure directories exist
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

// Process the uploaded file
$originalName = basename($_FILES['file']['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
$filename = $nameOnly . "_" . time() . "." . $ext;
$targetFile = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
    http_response_code(500);
    echo "Failed to move uploaded file. Error: " . $_FILES['file']['error'] . ", Temp file: " . $_FILES['file']['tmp_name'] . ", Target: " . $targetFile;
    exit;
}

// Include image optimizer
require_once __DIR__ . "/image_optimizer.php";

// Optimize image with multiple formats and sizes
$optimizationResults = ImageOptimizer::optimizeImage($targetFile, $uploadDir, $filename, true);

// Create traditional thumbnail for backward compatibility
$thumbFile = $thumbDir . $filename;
if (!createThumbnail($targetFile, $thumbFile, 400, 200)) {
    // If thumbnail creation fails, copy original as thumbnail
    copy($targetFile, $thumbFile);
}

// Generate blur placeholder for better perceived performance
$blurPlaceholder = ImageOptimizer::generateBlurPlaceholder($targetFile);

// Extract metadata
$metadata = extractMetadata($targetFile);

// Add optimization results to metadata
$metadata['optimization'] = $optimizationResults;
$metadata['blur_placeholder'] = $blurPlaceholder;
$metadata['optimized_sizes'] = array_keys($optimizationResults['sizes_generated'] ?? []);

// Save metadata to JSON
file_put_contents($targetFile . ".json", json_encode($metadata));

// Save to database
$uploader = $_SESSION['username'] ?? 'admin';
$ip = $_SERVER['REMOTE_ADDR'];
$album_id = isset($_POST['album_id']) && !empty($_POST['album_id']) && $_POST['album_id'] !== 'null' ? (int)$_POST['album_id'] : null;

// Check if album_id column exists
$columnExists = $conn->query("SHOW COLUMNS FROM photos LIKE 'album_id'")->num_rows > 0;

if ($columnExists) {
    $stmt = $conn->prepare("INSERT INTO photos (filename, album_id, uploaded_at, uploader, ip_address) VALUES (?, ?, NOW(), ?, ?)");
    $stmt->bind_param("siss", $filename, $album_id, $uploader, $ip);
} else {
    $stmt = $conn->prepare("INSERT INTO photos (filename, uploaded_at, uploader, ip_address) VALUES (?, NOW(), ?, ?)");
    $stmt->bind_param("sss", $filename, $uploader, $ip);
}
if (!$stmt->execute()) {
    http_response_code(500);
    echo "Database error: " . $stmt->error;
    exit;
}
$stmt->close();

// Clear cache after successful upload
clearGalleryCache();

echo "OK - File uploaded successfully";

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
    // For the gallery layout, we want to ensure the image covers the full area
    $scaleX = $width / $srcWidth;
    $scaleY = $height / $srcHeight;
    $scale = max($scaleX, $scaleY); // Use max to ensure coverage

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

    // Use high-quality resampling with better interpolation
    imagecopyresampled($thumb, $src, $destX, $destY, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Apply sharpening filter for better quality
    if (function_exists('imageconvolution')) {
        $sharpen = array(
            array(-1, -1, -1),
            array(-1, 16, -1),
            array(-1, -1, -1)
        );
        imageconvolution($thumb, $sharpen, 8, 0);
    }

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
            $success = imagejpeg($thumb, $dest, 100); // Maximum JPEG quality
    }

    imagedestroy($src);
    imagedestroy($thumb);

    return $success;
}

function extractMetadata($file) {
    $metadata = [];
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($file);
        if ($exif) {
            $metadata['EXIF'] = $exif;
        }
    }
    $metadata['filesize'] = filesize($file);
    $metadata['uploaded_at'] = time();
    return $metadata;
}
?>
