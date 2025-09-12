<?php
// cache_utils.php - Utility functions for cache management

function clearGalleryCache() {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) {
        return; // Cache directory doesn't exist, nothing to clear
    }

    // Clear all gallery cache files
    $cacheFiles = glob($cacheDir . 'gallery_cache_*.json');
    foreach ($cacheFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    // Also clear any other cache files that might exist
    $allCacheFiles = glob($cacheDir . '*.json');
    foreach ($allCacheFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

function getCacheStats() {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) {
        return ['directory_exists' => false, 'file_count' => 0, 'total_size' => 0];
    }

    $files = glob($cacheDir . '*.json');
    $totalSize = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            $totalSize += filesize($file);
        }
    }

    return [
        'directory_exists' => true,
        'file_count' => count($files),
        'total_size' => $totalSize,
        'total_size_human' => round($totalSize / 1024, 2) . ' KB'
    ];
}
?>