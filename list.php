<?php
// CLS & SI-optimized list.php with caching and WebP support
require __DIR__ . "/config.php";
require_once __DIR__ . "/image_optimizer.php";

// Handle database connection errors gracefully - use file storage in demo mode
if (!DB_AVAILABLE || !$db) {
    // Use file storage for demo mode - but for photos we need to scan the uploads directory
    global $fileStorage;
}

$isAdmin = !empty($_SESSION['is_admin']);

// Get album filter and sort parameters from query
$albumFilter = $_GET['album'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'date';
$sortOrder = $_GET['order'] ?? 'desc';

// Check if this is admin request for all photos
$adminAll = isset($_GET['admin_all']) && $isAdmin;

// Pagination parameters
$limit = (int)($_GET['limit'] ?? 20); // Default to 20 photos for fast loading
$offset = (int)($_GET['offset'] ?? 0);

// Check if we have cached results (5 minutes cache)
$cacheKey = $albumFilter . '_' . $sortBy . '_' . $sortOrder . '_' . $limit . '_' . $offset;
$cacheFile = __DIR__ . '/cache/gallery_cache_' . md5($cacheKey) . '_cls_si.json';
$cacheTime = 300; // 5 minutes

// Try to serve from cache first
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    // Serve cached content
    header("Content-Type: application/json");
    // Add cache headers for better performance
    header("Cache-Control: public, max-age=300");
    readfile($cacheFile);
    exit;
}


// Build ORDER BY clause
$orderBy = "p.uploaded_at DESC"; // default
if ($sortBy === 'date') {
    $orderBy = ($sortOrder === 'asc') ? "p.uploaded_at ASC" : "p.uploaded_at DESC";
} elseif ($sortBy === 'size') {
    // For size sorting, we'd need to get file size, but for now keep date sorting
    $orderBy = ($sortOrder === 'asc') ? "p.uploaded_at ASC" : "p.uploaded_at DESC";
}

// If no cache or expired, fetch from database
if (!DB_AVAILABLE || !$db) {
    // Demo mode: return empty array or scan uploads directory for photos
    $photos = [];
    $uploadsDir = __DIR__ . '/uploads';

    if (is_dir($uploadsDir)) {
        $files = scandir($uploadsDir);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $imageExtensions)) {
                $photos[] = [
                    'filename' => $file,
                    'uploaded_at' => date('Y-m-d H:i:s', filemtime($uploadsDir . '/' . $file)),
                    'uploader' => 'demo',
                    'ip_address' => '127.0.0.1',
                    'album_id' => null,
                    'album_name' => 'General'
                ];
            }
        }

        // Apply pagination
        $totalPhotos = count($photos);
        $photos = array_slice($photos, $offset, $limit);
    }

    // Cache the result
    $result = [
        'photos' => $photos,
        'total' => $totalPhotos ?? 0,
        'has_more' => (($offset + $limit) < ($totalPhotos ?? 0))
    ];

    // Save to cache
    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0777, true);
    }
    file_put_contents($cacheFile, json_encode($result));

    header("Content-Type: application/json");
    echo json_encode($result);
    exit;
}

// Check if albums table exists, fallback to simple query if not
$tableExistsResult = $db->query(DB_TYPE === 'sqlite' ? "SELECT name FROM sqlite_master WHERE type='table' AND name='albums'" : "SHOW TABLES LIKE 'albums'");
$tableExists = $tableExistsResult && (DB_TYPE === 'sqlite' ? $tableExistsResult->fetchColumn() : $tableExistsResult->num_rows > 0);

if ($tableExists) {
    if ($adminAll) {
        // Admin request for ALL photos - show everything
        $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id ORDER BY $orderBy LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    } elseif ($albumFilter !== 'all' && $albumFilter !== '' && is_numeric($albumFilter)) {
        // Filter by specific album with pagination
        $albumId = (int)$albumFilter;

        // Check if album is password protected
        $albumCheckStmt = $db->prepare("SELECT password FROM albums WHERE id = ?");
        $albumCheckStmt->bind_param("i", $albumId);
        $albumCheckStmt->execute();
        $albumResult = $albumCheckStmt->get_result();
        $album = $albumResult->fetch_assoc();
        $albumCheckStmt->close();

        // If album is password protected and user hasn't unlocked it (admins can see all)
        if ($album && !empty($album['password']) && !$isAdmin) {
            $unlockedAlbums = [];

            // Check temporarily unlocked albums from frontend (sent via header)
            $tempUnlocked = $_SERVER['HTTP_X_UNLOCKED_ALBUMS'] ?? '';
            if ($tempUnlocked) {
                $tempUnlocked = json_decode($tempUnlocked, true);
                if (is_array($tempUnlocked)) {
                    $unlockedAlbums = $tempUnlocked;
                    error_log("LIST: Found unlocked albums in header: " . json_encode($unlockedAlbums));
                }
            }

            // Also check session storage for backward compatibility
            $sessionUnlocked = $_SESSION['unlocked_albums'] ?? [];
            $unlockedAlbums = array_merge($unlockedAlbums, $sessionUnlocked);

            if (!isset($unlockedAlbums[$albumId])) {
                // Return empty array for locked albums
                header("Content-Type: application/json");
                echo json_encode([]);
                exit;
            }
        }

        $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id WHERE p.album_id = ? ORDER BY $orderBy LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $albumId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Show all photos with pagination
        if ($isAdmin) {
            // Admins can see all photos including from private albums
            $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id ORDER BY $orderBy LIMIT ? OFFSET ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
        } else {
            // Regular users: exclude ALL photos from private albums (albums with passwords)
            // Private albums should only be accessible when specifically selected, not in "All Photos"
            $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id WHERE (p.album_id IS NULL OR a.password IS NULL OR a.password = '') ORDER BY $orderBy LIMIT ? OFFSET ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    // Fallback query with pagination
    $query = "SELECT filename, uploaded_at, uploader, ip_address, NULL as album_id, 'General' as album_name FROM photos ORDER BY $orderBy LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}
$photos = [];

while ($row = $result->fetch_assoc()) {
    $filename = $row['filename'];
    $fullFile = __DIR__."/uploads/".$filename;
    $metaFile = __DIR__."/uploads/".$filename.".json";
    $metadata = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [];

    // Get optimized image paths with format negotiation
    $imagePaths = getOptimizedImagePaths($filename, $metadata);

    // Get blur placeholder if available
    $blurPlaceholder = $metadata['blur_placeholder'] ?? null;

    // Get file size
    $size = file_exists($fullFile) ? filesize($fullFile) : 0;

    // Get optimization info
    $optimization = $metadata['optimization'] ?? null;
    $optimizedSizes = $metadata['optimized_sizes'] ?? [];

    $p = [
        "filename" => $filename,
        "thumb" => $imagePaths['thumb'],
        "thumb_format" => $imagePaths['format'],
        "full_image" => $imagePaths['full'],
        "responsive_sizes" => $imagePaths['sizes'],
        "blur_placeholder" => $blurPlaceholder,
        "date" => $row['uploaded_at'],
        "size" => $size,
        "optimized_size" => $optimization['savings']['optimized'] ?? $size,
        "compression_savings" => $optimization['savings']['saved_percentage'] ?? 0,
        "metadata" => $metadata,
        "album_id" => $row['album_id'],
        "album_name" => $row['album_name'] ?? 'General'
    ];

    if ($isAdmin) {
        $p["uploader"] = $row['uploader'] ?? "Unknown";
        $p["ip"] = $row['ip_address'];
    }

    $photos[] = $p;
}

// Cache the results
if (!is_dir(__DIR__ . '/cache')) {
    mkdir(__DIR__ . '/cache', 0777, true);
}
file_put_contents($cacheFile, json_encode($photos));

header("Content-Type: application/json");
// Add cache headers for better performance
header("Cache-Control: public, max-age=300");
echo json_encode($photos);

/**
 * Get optimized image paths with format negotiation
 */
function getOptimizedImagePaths($filename, $metadata) {
    $basePath = "uploads/";
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

    // Check browser's preferred format
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $preferredFormat = ImageOptimizer::getOptimalFormat($acceptHeader);

    // Default paths
    $paths = [
        'thumb' => $basePath . 'thumbs/' . $filename,
        'full' => $basePath . $filename,
        'format' => 'jpg',
        'sizes' => []
    ];

    // Check for optimized versions
    if (isset($metadata['optimization'])) {
        $optimization = $metadata['optimization'];

        // Use WebP if available and preferred
        if ($preferredFormat === 'webp' && isset($optimization['optimized_files']['webp'])) {
            $webpPath = $basePath . $nameWithoutExt . '.webp';
            if (file_exists(__DIR__ . '/' . $webpPath)) {
                $paths['full'] = $webpPath;
                $paths['format'] = 'webp';
            }
        }

        // Add responsive sizes
        if (isset($optimization['sizes_generated'])) {
            foreach ($optimization['sizes_generated'] as $sizeName => $sizeData) {
                if (isset($sizeData['files'])) {
                    $sizePaths = [];
                    foreach ($sizeData['files'] as $format => $fileData) {
                        $sizePaths[$format] = $basePath . basename($fileData['path']);
                    }
                    $paths['sizes'][$sizeName] = $sizePaths;
                }
            }
        }
    }

    return $paths;
}
?>
