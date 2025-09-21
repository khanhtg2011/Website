<?php
// serve_image.php - On-demand WebP conversion and image serving
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/image_optimizer.php';

$filename = $_GET['file'] ?? '';
$size = $_GET['size'] ?? 'full'; // full, thumb, medium, large
$format = $_GET['format'] ?? 'auto'; // auto, webp, original

if (empty($filename)) {
    http_response_code(400);
    exit('Missing filename parameter');
}

// Security: prevent directory traversal
$filename = basename($filename);
$sourcePath = __DIR__ . '/uploads/' . $filename;

if (!file_exists($sourcePath)) {
    http_response_code(404);
    exit('File not found');
}

// Determine if browser supports WebP
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$supportsWebP = strpos($acceptHeader, 'image/webp') !== false;

// Determine format to serve
$serveWebP = false;
if ($format === 'webp' || ($format === 'auto' && $supportsWebP)) {
    $serveWebP = true;
}

// Generate cache filename
$nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
$cacheDir = __DIR__ . '/cache/images/';

// Create cache directory if it doesn't exist
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheFilename = $nameWithoutExt;
if ($size !== 'full') {
    $cacheFilename .= '_' . $size;
}
$cacheFilename .= $serveWebP ? '.webp' : '.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$cachePath = $cacheDir . $cacheFilename;

// Check if cached version exists and is newer than source
if (file_exists($cachePath) && filemtime($cachePath) >= filemtime($sourcePath)) {
    // Serve cached version
    $mime = $serveWebP ? 'image/webp' : mime_content_type($sourcePath);
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    exit;
}

// Generate the optimized version on-demand
try {
    if ($size === 'full') {
        // Full size image
        if ($serveWebP) {
            // Convert to WebP
            $result = ImageOptimizer::optimizeImage($sourcePath, $cacheDir, $cacheFilename, false);
            if ($result['success'] && isset($result['optimized_files']['webp'])) {
                $webpPath = $result['optimized_files']['webp']['path'];
                header('Content-Type: image/webp');
                header('Cache-Control: public, max-age=31536000');
                header('Content-Length: ' . filesize($webpPath));
                readfile($webpPath);
                exit;
            }
        } else {
            // Serve original optimized
            $result = ImageOptimizer::optimizeImage($sourcePath, $cacheDir, $cacheFilename, false);
            if ($result['success'] && isset($result['optimized_files']['original'])) {
                $originalPath = $result['optimized_files']['original']['path'];
                $mime = mime_content_type($originalPath);
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=31536000');
                header('Content-Length: ' . filesize($originalPath));
                readfile($originalPath);
                exit;
            }
        }
    } else {
        // Responsive size
        $sizes = [
            'thumb' => ['width' => 400, 'height' => 200],
            'medium' => ['width' => 800, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 900]
        ];

        if (!isset($sizes[$size])) {
            http_response_code(400);
            exit('Invalid size parameter');
        }

        $sizeConfig = $sizes[$size];

        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            http_response_code(400);
            exit('Invalid image');
        }

        $sourceImage = ImageOptimizer::loadImage($sourcePath, $imageInfo['mime']);
        if (!$sourceImage) {
            http_response_code(500);
            exit('Failed to load image');
        }

        // Strip metadata
        $strippedImage = ImageOptimizer::stripMetadata($sourceImage, $imageInfo['mime']);

        // Generate responsive size
        $result = ImageOptimizer::generateResponsiveSize(
            $strippedImage,
            $cacheDir,
            $filename,
            $size,
            $sizeConfig['width'],
            $sizeConfig['height'],
            90, // quality
            $imageInfo[0],
            $imageInfo[1]
        );

        imagedestroy($sourceImage);
        imagedestroy($strippedImage);

        if ($result['success'] && !empty($result['files'])) {
            $fileKey = $serveWebP ? 'webp' : 'jpg';
            if (isset($result['files'][$fileKey])) {
                $fileInfo = $result['files'][$fileKey];
                $mime = $serveWebP ? 'image/webp' : 'image/jpeg';
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=31536000');
                header('Content-Length: ' . $fileInfo['size']);
                readfile($fileInfo['path']);
                exit;
            }
        }
    }

    // Fallback: serve original file
    $mime = mime_content_type($sourcePath);
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
    readfile($sourcePath);

} catch (Exception $e) {
    error_log('Image serving error: ' . $e->getMessage());
    // Fallback: serve original file
    $mime = mime_content_type($sourcePath);
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=3600');
    readfile($sourcePath);
}
?>