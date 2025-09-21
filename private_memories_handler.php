<?php
session_start();

// Check if user has private gallery access (admin or password authenticated)
$hasPrivateAccess = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
                   (isset($_SESSION['private_gallery_access']) && $_SESSION['private_gallery_access'] === true);

if (!$hasPrivateAccess) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Private gallery access required']);
    exit;
}

require __DIR__ . "/config.php";

// Private memories storage paths
$privateDir = __DIR__ . '/private_memories';
$originalsDir = $privateDir . '/originals';
$thumbsDir = $privateDir . '/thumbs';
$metadataFile = $privateDir . '/memories.json';

// Ensure directories exist
if (!is_dir($privateDir)) mkdir($privateDir, 0755, true);
if (!is_dir($originalsDir)) mkdir($originalsDir, 0755, true);
if (!is_dir($thumbsDir)) mkdir($thumbsDir, 0755, true);

// Create metadata file if it doesn't exist
if (!file_exists($metadataFile)) {
    file_put_contents($metadataFile, json_encode([]));
}

// Load existing memories
function loadMemories() {
    global $metadataFile;
    if (file_exists($metadataFile)) {
        $data = file_get_contents($metadataFile);
        return json_decode($data, true) ?: [];
    }
    return [];
}

// Save memories
function saveMemories($memories) {
    global $metadataFile;
    file_put_contents($metadataFile, json_encode($memories, JSON_PRETTY_PRINT));
}

// Generate unique filename
function generateUniqueFilename($originalName, $extension = null) {
    $timestamp = time();
    $random = substr(md5(uniqid(mt_rand(), true)), 0, 8);

    if ($extension) {
        return $timestamp . '_' . $random . '.' . $extension;
    }

    // Extract extension from original name
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return $timestamp . '_' . $random . '.' . $ext;
}

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        handleUpload();
        break;

    case 'list':
        handleList();
        break;

    case 'delete':
        handleDelete();
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleUpload() {
    global $originalsDir, $thumbsDir;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST method required']);
        exit;
    }

    if (!isset($_FILES['file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $originalName = $file['name'];
    $tempPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $mimeType = $file['type'];

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/avi', 'video/mov', 'video/quicktime', 'video/webm'];
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['error' => 'Invalid file type. Only images and videos allowed.']);
        exit;
    }

    // Different size limits for images vs videos
    $maxSize = strpos($mimeType, 'video/') === 0 ? 100 * 1024 * 1024 : 10 * 1024 * 1024; // 100MB for videos, 10MB for images
    if ($fileSize > $maxSize) {
        $typeName = strpos($mimeType, 'video/') === 0 ? 'videos' : 'images';
        $maxSizeMB = strpos($mimeType, 'video/') === 0 ? '100MB' : '10MB';
        echo json_encode(['error' => "File too large. Maximum {$maxSizeMB} allowed for {$typeName}."]);
        exit;
    }

    // Generate unique filename
    $filename = generateUniqueFilename($originalName);

    // Move uploaded file to originals directory
    $originalPath = $originalsDir . '/' . $filename;
    if (!move_uploaded_file($tempPath, $originalPath)) {
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }

    $isVideo = strpos($mimeType, 'video/') === 0;
    $width = 0;
    $height = 0;
    $thumbPath = null;

    if ($isVideo) {
        // For videos, we'll use a video icon as thumbnail
        $thumbPath = 'data:image/svg+xml;base64,' . base64_encode('<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg"><rect width="400" height="400" fill="#dc3545"/><polygon points="150,120 280,200 150,280" fill="white"/><text x="200" y="350" text-anchor="middle" fill="white" font-size="24">VIDEO</text></svg>');
    } else {
        // Get image dimensions
        $imageInfo = getimagesize($originalPath);
        $width = $imageInfo[0] ?? 0;
        $height = $imageInfo[1] ?? 0;

        // Create thumbnail
        $actualThumbPath = $thumbsDir . '/' . $filename;
        createThumbnail($originalPath, $actualThumbPath, 400, 400);
        $thumbPath = 'private_memories/thumbs/' . $filename;
    }

    // Load existing memories and add new one
    $memories = loadMemories();

    $memory = [
        'id' => uniqid('mem_', true),
        'filename' => $filename,
        'original_filename' => $originalName,
        'uploaded_at' => date('Y-m-d H:i:s'),
        'uploader' => $_SESSION['username'] ?? 'admin',
        'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'file_size' => $fileSize,
        'mime_type' => $mimeType,
        'width' => $width,
        'height' => $height,
        'media_type' => $isVideo ? 'video' : 'image',
        'thumb' => $thumbPath,
        'metadata' => [],
        'created_at' => date('Y-m-d H:i:s')
    ];

    $memories[] = $memory;
    saveMemories($memories);

    echo json_encode([
        'success' => true,
        'memory' => $memory
    ]);
}

function handleList() {
    $memories = loadMemories();

    // Sort by upload date (newest first)
    usort($memories, function($a, $b) {
        return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
    });

    // Format for frontend
    $formattedMemories = array_map(function($memory) {
        global $originalsDir, $thumbsDir;

        $originalPath = 'private_memories/originals/' . $memory['filename'];
        $mediaType = $memory['media_type'] ?? 'image';
        $isVideo = $mediaType === 'video';

        // Check if files exist
        $originalExists = file_exists(__DIR__ . '/' . $originalPath);

        if ($isVideo) {
            // For videos, use the stored thumbnail (video icon)
            $thumbPath = $memory['thumb'] ?? 'data:image/svg+xml;base64,' . base64_encode('<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg"><rect width="400" height="400" fill="#dc3545"/><polygon points="150,120 280,200 150,280" fill="white"/><text x="200" y="350" text-anchor="middle" fill="white" font-size="24">VIDEO</text></svg>');
        } else {
            $thumbPath = 'private_memories/thumbs/' . $memory['filename'];
            $thumbExists = file_exists(__DIR__ . '/' . $thumbPath);
            if (!$thumbExists) {
                $thumbPath = $originalPath; // fallback to original if thumb doesn't exist
            }
        }

        return [
            'id' => $memory['id'],
            'filename' => $memory['filename'],
            'original_filename' => $memory['original_filename'],
            'thumb' => $thumbPath,
            'full_image' => $originalExists ? $originalPath : null,
            'full_video' => $isVideo && $originalExists ? $originalPath : null,
            'date' => $memory['uploaded_at'],
            'size' => $memory['file_size'],
            'width' => $memory['width'],
            'height' => $memory['height'],
            'media_type' => $mediaType,
            'is_video' => $isVideo,
            'metadata' => $memory['metadata'] ?? [],
            'uploader' => $memory['uploader'],
            'ip' => $memory['ip_address']
        ];
    }, $memories);

    echo json_encode($formattedMemories);
}

function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST method required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';

    if (!$filename) {
        echo json_encode(['error' => 'Filename required']);
        exit;
    }

    $memories = loadMemories();

    // Find and remove the memory
    $found = false;
    $memories = array_filter($memories, function($memory) use ($filename, &$found) {
        if ($memory['filename'] === $filename) {
            $found = true;
            return false; // Remove this item
        }
        return true; // Keep this item
    });

    if (!$found) {
        echo json_encode(['error' => 'Memory not found']);
        exit;
    }

    // Delete physical files
    global $originalsDir, $thumbsDir;
    $originalPath = $originalsDir . '/' . $filename;
    $thumbPath = $thumbsDir . '/' . $filename;

    if (file_exists($originalPath)) unlink($originalPath);
    if (file_exists($thumbPath)) unlink($thumbPath);

    // Save updated memories
    saveMemories(array_values($memories));

    echo json_encode(['success' => true]);
}

function createThumbnail($sourcePath, $destPath, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Calculate thumbnail size
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $thumbWidth = round($width * $ratio);
    $thumbHeight = round($height * $ratio);

    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$source) return false;

    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

    // Save thumbnail
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumbnail, $destPath, 85);
            break;
        case 'image/png':
            imagepng($thumbnail, $destPath, 8);
            break;
        case 'image/gif':
            imagegif($thumbnail, $destPath);
            break;
        case 'image/webp':
            imagewebp($thumbnail, $destPath, 85);
            break;
    }

    // Clean up memory
    imagedestroy($source);
    imagedestroy($thumbnail);

    return true;
}
?>