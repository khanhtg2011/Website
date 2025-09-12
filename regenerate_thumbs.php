
<?php
require __DIR__ . "/config.php";

if (empty($_SESSION['is_admin'])) {
    die("Admin only");
}

$result = $conn->query("SELECT filename FROM photos");
$count = 0;
$total = $result->num_rows;
while ($row = $result->fetch_assoc()) {
    $filename = $row['filename'];
    $targetFile = __DIR__ . "/uploads/" . $filename;
    $thumbFile = __DIR__ . "/uploads/thumbs/" . $filename;

    if (file_exists($targetFile)) {
        createThumbnail($targetFile, $thumbFile, 200);
        echo "Regenerated thumb for $filename<br>";
        $count++;
    } else {
        echo "Original file missing for $filename<br>";
    }
}
echo "Processed $count/$total thumbnails<br>";

// Clear gallery cache to force refresh
$galleryCache = __DIR__ . '/cache/gallery_cache_cls_si.json';
if (file_exists($galleryCache)) {
    unlink($galleryCache);
    echo "Cache cleared<br>";
}

echo "Done";

function createThumbnail($source, $dest, $thumbWidth) {
    if (!function_exists('imagecreatefromjpeg')) {
        return false;
    }

    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png': $src = imagecreatefrompng($source); break;
        case 'image/gif': $src = imagecreatefromgif($source); break;
        default: return false;
    }

    $srcWidth = imagesx($src);
    $srcHeight = imagesy($src);
    $aspect = $srcWidth / $srcHeight;

    $thumbWidth = 200;
    $thumbHeight = $thumbWidth / $aspect;

    $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $srcWidth, $srcHeight);

    imagejpeg($thumb, $dest, 85);
    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}
?>