<?php
// CLS & SI-optimized list.php with caching and WebP support
require __DIR__ . "/config.php";

// Handle database connection errors gracefully
if ($conn->connect_error) {
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed",
        "message" => "Unable to connect to database. Please check your configuration."
    ]);
    exit;
}

$isAdmin = !empty($_SESSION['is_admin']);

// Get album filter and sort parameters from query
$albumFilter = $_GET['album'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'date';
$sortOrder = $_GET['order'] ?? 'desc';

// Check if we have cached results (5 minutes cache)
$cacheKey = $albumFilter . '_' . $sortBy . '_' . $sortOrder;
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
// Check if albums table exists, fallback to simple query if not
$tableExists = $conn->query("SHOW TABLES LIKE 'albums'")->num_rows > 0;

if ($tableExists) {
    if ($albumFilter !== 'all' && $albumFilter !== '' && is_numeric($albumFilter)) {
        // Filter by specific album
        $albumId = (int)$albumFilter;
        $stmt = $conn->prepare("SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id WHERE p.album_id = ? ORDER BY p.uploaded_at DESC");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Show all photos
        $query = "SELECT p.filename, p.uploaded_at, p.uploader, p.ip_address, p.album_id, a.name as album_name FROM photos p LEFT JOIN albums a ON p.album_id = a.id ORDER BY p.uploaded_at DESC";
        $result = $conn->query($query);
    }
} else {
    $query = "SELECT filename, uploaded_at, uploader, ip_address, NULL as album_id, 'General' as album_name FROM photos ORDER BY uploaded_at DESC";
    $result = $conn->query($query);
}
$photos = [];

while ($row = $result->fetch_assoc()) {
    $thumbFile = "uploads/thumbs/".$row['filename'];
    $webpFile  = preg_replace('/\.[a-zA-Z0-9]+$/','.webp',$thumbFile);
    $fullFile  = __DIR__."/uploads/".$row['filename'];

    // Prefer WebP, then PNG, then original thumbnail for better performance
    if (file_exists(__DIR__."/".$webpFile)) {
        $thumb = $webpFile;
    } elseif (file_exists(__DIR__."/".$thumbFile)) {
        $thumb = $thumbFile;
    } else {
        // Fallback to original file if no thumbnail exists
        $thumb = "uploads/" . $row['filename'];
    }
    $size = file_exists($fullFile) ? filesize($fullFile) : 0;

    $metaFile = __DIR__."/uploads/".$row['filename'].".json";
    $metadata = file_exists($metaFile) ? json_decode(file_get_contents($metaFile),true) : [];

    $p = [
        "filename" => $row['filename'],
        "thumb" => $thumb,
        "date" => $row['uploaded_at'],
        "size" => $size,
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
?>
