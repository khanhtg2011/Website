<?php
// image_optimizer.php - Advanced image optimization utilities
class ImageOptimizer {

    private static $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private static $qualitySettings = [
        'thumbnail' => 85,
        'gallery' => 90,
        'full' => 95
    ];

    /**
     * Optimize image for web delivery with multiple formats and sizes
     */
    public static function optimizeImage($sourcePath, $outputDir, $filename, $generateSizes = true) {
        if (!file_exists($sourcePath)) {
            return ['success' => false, 'error' => 'Source file not found'];
        }

        $results = [
            'success' => true,
            'original_size' => filesize($sourcePath),
            'optimized_files' => [],
            'webp_converted' => false,
            'sizes_generated' => []
        ];

        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }

        $mime = $imageInfo['mime'];
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];

        // Load source image
        $sourceImage = self::loadImage($sourcePath, $mime);
        if (!$sourceImage) {
            return ['success' => false, 'error' => 'Failed to load image'];
        }

        // Strip EXIF data for smaller file size
        $strippedImage = self::stripMetadata($sourceImage, $mime);

        // Generate WebP version (best compression)
        $webpPath = $outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        if (self::saveAsWebP($strippedImage, $webpPath, self::$qualitySettings['full'])) {
            $results['webp_converted'] = true;
            $results['optimized_files']['webp'] = [
                'path' => $webpPath,
                'size' => filesize($webpPath),
                'format' => 'webp'
            ];
        }

        // Generate responsive sizes if requested
        if ($generateSizes) {
            $sizes = [
                'thumb' => ['width' => 400, 'height' => 200, 'quality' => self::$qualitySettings['thumbnail']],
                'medium' => ['width' => 800, 'height' => 600, 'quality' => self::$qualitySettings['gallery']],
                'large' => ['width' => 1200, 'height' => 900, 'quality' => self::$qualitySettings['gallery']]
            ];

            foreach ($sizes as $sizeName => $sizeConfig) {
                $sizeResults = self::generateResponsiveSize(
                    $strippedImage,
                    $outputDir,
                    $filename,
                    $sizeName,
                    $sizeConfig['width'],
                    $sizeConfig['height'],
                    $sizeConfig['quality'],
                    $originalWidth,
                    $originalHeight
                );

                if ($sizeResults['success']) {
                    $results['sizes_generated'][$sizeName] = $sizeResults;
                }
            }
        }

        // Save optimized original format
        $optimizedPath = $outputDir . '/' . $filename;
        $optimizedSize = self::saveOptimized($strippedImage, $optimizedPath, $mime, self::$qualitySettings['full']);

        if ($optimizedSize) {
            $results['optimized_files']['original'] = [
                'path' => $optimizedPath,
                'size' => $optimizedSize,
                'format' => strtolower(pathinfo($filename, PATHINFO_EXTENSION))
            ];
        }

        // Calculate savings
        $totalOptimizedSize = array_sum(array_column($results['optimized_files'], 'size'));
        $results['savings'] = [
            'original' => $results['original_size'],
            'optimized' => $totalOptimizedSize,
            'saved_bytes' => $results['original_size'] - $totalOptimizedSize,
            'saved_percentage' => round((($results['original_size'] - $totalOptimizedSize) / $results['original_size']) * 100, 2)
        ];

        imagedestroy($sourceImage);
        imagedestroy($strippedImage);

        return $results;
    }

    /**
     * Generate responsive image size
     */
    private static function generateResponsiveSize($sourceImage, $outputDir, $filename, $sizeName, $maxWidth, $maxHeight, $quality, $originalWidth, $originalHeight) {
        // Calculate dimensions maintaining aspect ratio
        $aspectRatio = $originalWidth / $originalHeight;

        if ($maxWidth / $maxHeight > $aspectRatio) {
            $newWidth = $maxHeight * $aspectRatio;
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $aspectRatio;
        }

        $newWidth = (int)$newWidth;
        $newHeight = (int)$newHeight;

        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency
        if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefill($resizedImage, 0, 0, $transparent);
        }

        // Resample with high quality
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Apply sharpening
        self::applySharpening($resizedImage);

        // Generate filename
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $webpPath = $outputDir . '/' . $nameWithoutExt . '_' . $sizeName . '.webp';
        $jpgPath = $outputDir . '/' . $nameWithoutExt . '_' . $sizeName . '.jpg';

        $results = [
            'success' => false,
            'files' => []
        ];

        // Save WebP version
        if (self::saveAsWebP($resizedImage, $webpPath, $quality)) {
            $results['files']['webp'] = [
                'path' => $webpPath,
                'size' => filesize($webpPath),
                'width' => $newWidth,
                'height' => $newHeight
            ];
        }

        // Save JPEG fallback
        if (self::saveAsJpeg($resizedImage, $jpgPath, $quality)) {
            $results['files']['jpg'] = [
                'path' => $jpgPath,
                'size' => filesize($jpgPath),
                'width' => $newWidth,
                'height' => $newHeight
            ];
        }

        if (!empty($results['files'])) {
            $results['success'] = true;
        }

        imagedestroy($resizedImage);
        return $results;
    }

    /**
     * Load image based on MIME type
     */
    private static function loadImage($path, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Strip metadata from image
     */
    private static function stripMetadata($image, $mime) {
        // For JPEG, we can't easily strip EXIF with GD, but we can create a clean copy
        // This effectively removes most metadata by recreating the image
        $width = imagesx($image);
        $height = imagesy($image);

        $cleanImage = imagecreatetruecolor($width, $height);

        // Handle transparency for PNG/GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($cleanImage, false);
            imagesavealpha($cleanImage, true);
            $transparent = imagecolorallocatealpha($cleanImage, 0, 0, 0, 127);
            imagefill($cleanImage, 0, 0, $transparent);
        }

        imagecopy($cleanImage, $image, 0, 0, 0, 0, $width, $height);
        return $cleanImage;
    }

    /**
     * Save as WebP with optimization
     */
    private static function saveAsWebP($image, $path, $quality = 90) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        return imagewebp($image, $path, $quality);
    }

    /**
     * Save as optimized JPEG
     */
    private static function saveAsJpeg($image, $path, $quality = 90) {
        return imagejpeg($image, $path, $quality);
    }

    /**
     * Save with optimal settings based on format
     */
    private static function saveOptimized($image, $path, $mime, $quality = 90) {
        $success = false;

        switch ($mime) {
            case 'image/jpeg':
                $success = imagejpeg($image, $path, $quality);
                break;
            case 'image/png':
                $success = imagepng($image, $path, 9); // Maximum compression
                break;
            case 'image/gif':
                $success = imagegif($image, $path);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($image, $path, $quality);
                } else {
                    $success = imagejpeg($image, $path, $quality);
                }
                break;
        }

        return $success ? filesize($path) : false;
    }

    /**
     * Apply sharpening filter for better quality
     */
    private static function applySharpening($image) {
        if (!function_exists('imageconvolution')) {
            return;
        }

        $sharpen = array(
            array(-1, -1, -1),
            array(-1, 16, -1),
            array(-1, -1, -1)
        );

        imageconvolution($image, $sharpen, 8, 0);
    }

    /**
     * Generate blur placeholder for perceived performance
     */
    public static function generateBlurPlaceholder($sourcePath, $width = 20, $height = 10) {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $sourceImage = self::loadImage($sourcePath, $imageInfo['mime']);
        if (!$sourceImage) {
            return false;
        }

        // Create tiny version for blur placeholder
        $blurImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($blurImage, $sourceImage, 0, 0, 0, 0, $width, $height, $imageInfo[0], $imageInfo[1]);

        // Apply heavy blur
        for ($i = 0; $i < 3; $i++) {
            imagefilter($blurImage, IMG_FILTER_GAUSSIAN_BLUR);
        }

        // Convert to base64 data URL
        ob_start();
        imagejpeg($blurImage, null, 70);
        $imageData = ob_get_clean();
        $base64 = base64_encode($imageData);

        imagedestroy($sourceImage);
        imagedestroy($blurImage);

        return 'data:image/jpeg;base64,' . $base64;
    }

    /**
     * Get optimal image format based on browser support
     */
    public static function getOptimalFormat($acceptHeader = null) {
        if (!$acceptHeader) {
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        }

        // Check for WebP support
        if (strpos($acceptHeader, 'image/webp') !== false) {
            return 'webp';
        }

        // Check for AVIF support (future-proofing)
        if (strpos($acceptHeader, 'image/avif') !== false) {
            return 'avif';
        }

        return 'jpg'; // Fallback
    }
}
?>