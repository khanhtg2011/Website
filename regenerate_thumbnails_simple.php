<?php
// regenerate_thumbnails_simple.php - Simplified version for better compatibility
require __DIR__ . "/config.php";
require __DIR__ . "/cache_utils.php";

// Check if this is an AJAX request
$isAjax = false;
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $isAjax = true;
} elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjax = true;
} elseif (isset($_GET['action'])) {
    $isAjax = true;
}

if ($isAjax) {
    // Handle AJAX request
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'scan':
            $result = scanImages();
            echo json_encode($result);
            break;

        case 'generate':
            $data = json_decode(file_get_contents('php://input'), true);
            $filename = $data['filename'] ?? '';
            $result = generateThumbnail($filename);
            echo json_encode($result);
            break;

        case 'clear':
            $result = clearAllThumbnails();
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// HTML Interface
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerate Thumbnails - Simple Version</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #007bff; }
        .stat-box h3 { margin: 0 0 8px 0; color: #333; }
        .stat-box .number { font-size: 24px; font-weight: bold; color: #007bff; }
        .progress { margin: 20px 0; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #007bff, #0056b3); transition: width 0.3s ease; }
        .log { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin: 20px 0; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .log-entry { margin: 5px 0; padding: 2px 0; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #007bff; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerate Thumbnails (Simple Version)</h1>
        <p>This simplified version should work on all server configurations.</p>

        <div class="stats">
            <div class="stat-box">
                <h3>Images Found</h3>
                <div class="number" id="totalImages">0</div>
            </div>
            <div class="stat-box">
                <h3>Missing Thumbnails</h3>
                <div class="number" id="missingThumbs">0</div>
            </div>
            <div class="stat-box">
                <h3>Processed</h3>
                <div class="number" id="processed">0</div>
            </div>
            <div class="stat-box">
                <h3>Success Rate</h3>
                <div class="number" id="successRate">0%</div>
            </div>
        </div>

        <div class="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
        </div>

        <button class="btn" id="startBtn" onclick="startRegeneration()">Start Regeneration</button>
        <button class="btn btn-danger" onclick="clearAllThumbnails()">Clear All Thumbnails</button>
        <button class="btn" onclick="location.reload()">Refresh</button>

        <div class="log" id="log">
            <div class="log-entry log-info">Ready to start thumbnail regeneration...</div>
        </div>
    </div>

    <script>
        let isRunning = false;
        let processed = 0;
        let successful = 0;
        let failed = 0;

        function log(message, type) {
            type = type || 'info';
            const log = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            entry.textContent = message;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        function updateStats(total, missing, processed, successful, failed) {
            document.getElementById('totalImages').textContent = total;
            document.getElementById('missingThumbs').textContent = missing;
            document.getElementById('processed').textContent = processed;

            const totalProcessed = successful + failed;
            const successRate = totalProcessed > 0 ? Math.round((successful / totalProcessed) * 100) : 0;
            document.getElementById('successRate').textContent = successRate + '%';

            const progress = total > 0 ? (processed / total) * 100 : 0;
            document.getElementById('progressFill').style.width = progress + '%';
        }

        async function startRegeneration() {
            if (isRunning) return;

            isRunning = true;
            document.getElementById('startBtn').disabled = true;
            document.getElementById('startBtn').textContent = 'Processing...';

            log('Starting thumbnail regeneration...', 'info');

            try {
                // First, scan for images
                const scanResponse = await fetch('regenerate_thumbnails_simple.php?action=scan&ajax=1');
                const scanData = await scanResponse.json();

                log('Found ' + scanData.total + ' images, ' + scanData.missing + ' missing thumbnails', 'info');
                updateStats(scanData.total, scanData.missing, 0, 0, 0);

                if (scanData.missing === 0) {
                    log('All thumbnails are up to date!', 'success');
                    return;
                }

                // Process images in batches
                processed = 0;
                successful = 0;
                failed = 0;

                for (const image of scanData.images) {
                    try {
                        const response = await fetch('regenerate_thumbnails_simple.php?action=generate&ajax=1', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ filename: image })
                        });

                        const result = await response.json();

                        if (result.success) {
                            log('✅ Generated thumbnail for: ' + image, 'success');
                            successful++;
                        } else {
                            log('❌ Failed to generate thumbnail for: ' + image + ' (' + result.error + ')', 'error');
                            failed++;
                        }
                    } catch (error) {
                        log('❌ Error processing: ' + image + ' (' + error.message + ')', 'error');
                        failed++;
                    }

                    processed++;
                    updateStats(scanData.total, scanData.missing, processed, successful, failed);

                    // Small delay to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 100));
                }

                log('Regeneration complete! ' + successful + ' successful, ' + failed + ' failed', successful > failed ? 'success' : 'error');

            } catch (error) {
                log('Fatal error: ' + error.message, 'error');
            } finally {
                isRunning = false;
                document.getElementById('startBtn').disabled = false;
                document.getElementById('startBtn').textContent = 'Start Regeneration';
            }
        }

        async function clearAllThumbnails() {
            if (!confirm('Are you sure you want to delete all thumbnails? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('regenerate_thumbnails_simple.php?action=clear&ajax=1');
                const result = await response.json();

                if (result.success) {
                    log('🗑️ Cleared ' + result.deleted + ' thumbnails', 'info');
                    location.reload();
                } else {
                    log('❌ Failed to clear thumbnails: ' + result.error, 'error');
                }
            } catch (error) {
                log('❌ Error clearing thumbnails: ' + error.message, 'error');
            }
        }

        // Initial scan on page load
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('regenerate_thumbnails_simple.php?action=scan&ajax=1');
                const data = await response.json();
                updateStats(data.total, data.missing, 0, 0, 0);
                log('Initial scan: ' + data.total + ' images, ' + data.missing + ' missing thumbnails', 'info');
            } catch (error) {
                log('Failed to scan images: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>

<?php
// PHP Functions (only executed for AJAX requests)

function scanImages() {
    $uploadDir = __DIR__ . "/uploads/";
    $thumbDir = __DIR__ . "/uploads/thumbs/";

    if (!is_dir($uploadDir)) {
        return ['success' => false, 'error' => 'Upload directory not found'];
    }

    // Get all image files
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

    return [
        'success' => true,
        'total' => count($images),
        'missing' => $missingThumbs,
        'images' => $images
    ];
}

function generateThumbnail($filename) {
    if (empty($filename)) {
        return ['success' => false, 'error' => 'No filename provided'];
    }

    $uploadDir = __DIR__ . "/uploads/";
    $thumbDir = __DIR__ . "/uploads/thumbs/";
    $sourceFile = $uploadDir . $filename;
    $thumbFile = $thumbDir . $filename;

    // Check if source file exists
    if (!file_exists($sourceFile)) {
        return ['success' => false, 'error' => 'Source file not found'];
    }

    // Ensure thumbnail directory exists
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }

    // Generate thumbnail with proper sizing for the gallery layout
    // The gallery uses height: 200px with object-fit: cover, so we create thumbnails
    // that are 400px wide by 200px tall to ensure proper aspect ratio coverage
    if (createThumbnail($sourceFile, $thumbFile, 400, 200)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to create thumbnail'];
    }
}

function clearAllThumbnails() {
    $thumbDir = __DIR__ . "/uploads/thumbs/";

    if (!is_dir($thumbDir)) {
        return ['success' => true, 'deleted' => 0, 'message' => 'Thumbnail directory not found'];
    }

    $deleted = 0;
    $files = scandir($thumbDir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $filePath = $thumbDir . $file;
        if (is_file($filePath) && unlink($filePath)) {
            $deleted++;
        }
    }

    // Clear gallery cache
    clearGalleryCache();

    return ['success' => true, 'deleted' => $deleted];
}

// Perfect thumbnail creation function - High quality, no artifacts!
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
?>