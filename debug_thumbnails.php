<?php
// debug_thumbnails.php - Advanced thumbnail debugging
echo "<h1>Advanced Thumbnail Debug</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

// Check if we can access the gallery data
echo "<h2>Step 1: Testing Gallery Data Access</h2>";
$galleryUrl = 'list.php';
echo "<p>Testing gallery URL: <code>$galleryUrl</code></p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'X-Requested-With: XMLHttpRequest'
    ]
]);

$galleryResponse = file_get_contents($galleryUrl, false, $context);
if ($galleryResponse === false) {
    echo "<p class='error'>❌ Cannot access gallery data - this is a major issue!</p>";
    echo "<p>Possible causes:</p>";
    echo "<ul>";
    echo "<li>Database connection failed</li>";
    echo "<li>PHP errors in list.php</li>";
    echo "<li>File permissions on list.php</li>";
    echo "</ul>";
} else {
    echo "<p class='success'>✅ Gallery data accessible</p>";
    $galleryData = json_decode($galleryResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p class='error'>❌ Gallery returned invalid JSON: " . json_last_error_msg() . "</p>";
        echo "<pre>" . htmlspecialchars(substr($galleryResponse, 0, 500)) . "...</pre>";
    } else {
        $photoCount = count($galleryData);
        echo "<p class='success'>✅ Gallery returned $photoCount photos</p>";

        if ($photoCount > 0) {
            echo "<h3>Sample Photo Data:</h3>";
            $samplePhoto = $galleryData[0];
            echo "<pre>" . json_encode($samplePhoto, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
}

// Test thumbnail file access
echo "<h2>Step 2: Testing Thumbnail File Access</h2>";
$thumbDir = __DIR__ . "/uploads/thumbs/";
$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($thumbDir)) {
    echo "<p class='error'>❌ Thumbs directory does not exist: $thumbDir</p>";
} else {
    echo "<p class='success'>✅ Thumbs directory exists</p>";

    $thumbFiles = glob($thumbDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    echo "<p>Found " . count($thumbFiles) . " thumbnail files</p>";

    if (count($thumbFiles) > 0) {
        echo "<h3>Testing First 3 Thumbnails:</h3>";
        echo "<table>";
        echo "<tr><th>Filename</th><th>File Size</th><th>HTTP Access</th><th>Image Info</th><th>Visual Test</th></tr>";

        for ($i = 0; $i < min(3, count($thumbFiles)); $i++) {
            $file = $thumbFiles[$i];
            $filename = basename($file);
            $webPath = "uploads/thumbs/" . $filename;

            echo "<tr>";
            echo "<td>$filename</td>";

            // File size
            $fileSize = filesize($file);
            echo "<td>" . ($fileSize > 0 ? number_format($fileSize) . " bytes" : "<span class='error'>0 bytes</span>") . "</td>";

            // HTTP access test
            $httpTest = @file_get_contents($webPath);
            if ($httpTest === false) {
                echo "<td class='error'>❌ Failed</td>";
            } else {
                echo "<td class='success'>✅ OK (" . strlen($httpTest) . " bytes)</td>";
            }

            // Image info
            $imageInfo = @getimagesize($file);
            if ($imageInfo) {
                echo "<td class='success'>{$imageInfo[0]}x{$imageInfo[1]} {$imageInfo['mime']}</td>";
            } else {
                echo "<td class='error'>❌ Cannot read image</td>";
            }

            // Visual test
            echo "<td><img src='$webPath' style='max-width: 100px; max-height: 100px; border: 1px solid #ccc;' onerror=\"this.outerHTML='<span class=error>❌ Failed to load</span>'\"></td>";

            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check for common issues
echo "<h2>Step 3: Common Issue Detection</h2>";
$issues = [];

if (!function_exists('imagecreatefromjpeg')) {
    $issues[] = "PHP GD extension not available - thumbnails cannot be generated";
}

if (!is_dir($uploadDir)) {
    $issues[] = "Uploads directory does not exist";
}

if (!is_dir($thumbDir)) {
    $issues[] = "Thumbs directory does not exist";
}

if (!is_readable($uploadDir)) {
    $issues[] = "Uploads directory is not readable";
}

if (!is_readable($thumbDir)) {
    $issues[] = "Thumbs directory is not readable";
}

if (count($thumbFiles) == 0) {
    $issues[] = "No thumbnail files found in thumbs directory";
}

if (empty($issues)) {
    echo "<p class='success'>✅ No common issues detected</p>";
} else {
    echo "<p class='warning'>⚠️ Found " . count($issues) . " potential issues:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "<h2>Step 4: Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Check file permissions:</strong> Make sure uploads/ and uploads/thumbs/ have 755 permissions</li>";
echo "<li><strong>Clear browser cache:</strong> Hard refresh (Ctrl+F5) your gallery page</li>";
echo "<li><strong>Check .htaccess:</strong> Make sure it doesn't block image access</li>";
echo "<li><strong>Regenerate thumbnails:</strong> Run regenerate_thumbnails.php if thumbnails are corrupted</li>";
echo "<li><strong>Check PHP errors:</strong> Look at your server error logs</li>";
echo "</ol>";

echo "<p><strong>Next step:</strong> Run this script and share the results with me!</p>";
?>