<?php
// final_thumbnail_check.php - Comprehensive thumbnail analysis
echo "<h1>Final Thumbnail Analysis</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

$issues = [];
$fixes = [];

// Check directories
$thumbDir = __DIR__ . "/uploads/thumbs/";
$uploadDir = __DIR__ . "/uploads/";

echo "<h2>Step 1: Directory Analysis</h2>";
echo "<table>";
echo "<tr><th>Directory</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Permissions</th><th>File Count</th></tr>";

// Check uploads directory
$uploadExists = is_dir($uploadDir);
$uploadReadable = is_readable($uploadDir);
$uploadWritable = is_writable($uploadDir);
$uploadPerms = $uploadExists ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A';
$uploadFiles = $uploadExists ? count(glob($uploadDir . "*")) : 0;

echo "<tr>";
echo "<td>uploads/</td>";
echo "<td class='" . ($uploadExists ? 'success' : 'error') . "'>" . ($uploadExists ? 'YES' : 'NO') . "</td>";
echo "<td class='" . ($uploadReadable ? 'success' : 'error') . "'>" . ($uploadReadable ? 'YES' : 'NO') . "</td>";
echo "<td class='" . ($uploadWritable ? 'success' : 'error') . "'>" . ($uploadWritable ? 'YES' : 'NO') . "</td>";
echo "<td>$uploadPerms</td>";
echo "<td>$uploadFiles files</td>";
echo "</tr>";

// Check thumbs directory
$thumbExists = is_dir($thumbDir);
$thumbReadable = is_readable($thumbDir);
$thumbWritable = is_writable($thumbDir);
$thumbPerms = $thumbExists ? substr(sprintf('%o', fileperms($thumbDir)), -4) : 'N/A';
$thumbFiles = $thumbExists ? count(glob($thumbDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE)) : 0;

echo "<tr>";
echo "<td>uploads/thumbs/</td>";
echo "<td class='" . ($thumbExists ? 'success' : 'error') . "'>" . ($thumbExists ? 'YES' : 'NO') . "</td>";
echo "<td class='" . ($thumbReadable ? 'success' : 'error') . "'>" . ($thumbReadable ? 'YES' : 'NO') . "</td>";
echo "<td class='" . ($thumbWritable ? 'success' : 'error') . "'>" . ($thumbWritable ? 'YES' : 'NO') . "</td>";
echo "<td>$thumbPerms</td>";
echo "<td>$thumbFiles files</td>";
echo "</tr>";
echo "</table>";

// Fix permissions if needed
if ($thumbExists && (!$thumbReadable || $thumbPerms !== '0755')) {
    if (chmod($thumbDir, 0755)) {
        $fixes[] = "Fixed thumbs directory permissions to 755";
        echo "<p class='success'>✅ Fixed thumbs directory permissions</p>";
    }
}

if ($uploadExists && (!$uploadReadable || $uploadPerms !== '0755')) {
    if (chmod($uploadDir, 0755)) {
        $fixes[] = "Fixed uploads directory permissions to 755";
        echo "<p class='success'>✅ Fixed uploads directory permissions</p>";
    }
}

echo "<h2>Step 2: Thumbnail File Analysis</h2>";

if ($thumbFiles > 0) {
    echo "<p>Analyzing $thumbFiles thumbnail files...</p>";

    $thumbFileList = glob($thumbDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    $emptyFiles = [];
    $corruptedFiles = [];
    $goodFiles = [];
    $totalSize = 0;

    echo "<table>";
    echo "<tr><th>Filename</th><th>Size</th><th>Permissions</th><th>Image Info</th><th>Status</th></tr>";

    foreach (array_slice($thumbFileList, 0, 10) as $file) { // Check first 10 files
        $filename = basename($file);
        $fileSize = filesize($file);
        $totalSize += $fileSize;
        $filePerms = substr(sprintf('%o', fileperms($file)), -4);

        echo "<tr>";
        echo "<td>$filename</td>";
        echo "<td>" . number_format($fileSize) . " bytes</td>";
        echo "<td>$filePerms</td>";

        if ($fileSize == 0) {
            echo "<td class='error'>EMPTY FILE</td>";
            echo "<td class='error'>❌ 0 bytes</td>";
            $emptyFiles[] = $filename;
        } else {
            $imageInfo = @getimagesize($file);
            if ($imageInfo) {
                echo "<td class='success'>{$imageInfo[0]}x{$imageInfo[1]} {$imageInfo['mime']}</td>";
                echo "<td class='success'>✅ Valid</td>";
                $goodFiles[] = $filename;

                // Fix file permissions if needed
                if ($filePerms !== '0644' && chmod($file, 0644)) {
                    $fixes[] = "Fixed permissions for $filename";
                }
            } else {
                echo "<td class='error'>Cannot read image</td>";
                echo "<td class='error'>❌ Corrupted</td>";
                $corruptedFiles[] = $filename;
            }
        }
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Total files:</strong> $thumbFiles</li>";
    echo "<li><strong>Good files:</strong> " . count($goodFiles) . "</li>";
    echo "<li><strong>Empty files:</strong> " . count($emptyFiles) . "</li>";
    echo "<li><strong>Corrupted files:</strong> " . count($corruptedFiles) . "</li>";
    echo "<li><strong>Total size:</strong> " . number_format($totalSize) . " bytes</li>";
    echo "<li><strong>Average size:</strong> " . number_format($totalSize / max(1, count($thumbFileList))) . " bytes</li>";
    echo "</ul>";

    if (!empty($emptyFiles)) {
        $issues[] = count($emptyFiles) . " thumbnail files are empty (0 bytes)";
    }

    if (!empty($corruptedFiles)) {
        $issues[] = count($corruptedFiles) . " thumbnail files are corrupted";
    }

} else {
    echo "<p class='error'>❌ No thumbnail files found!</p>";
    $issues[] = "No thumbnail files exist";
}

echo "<h2>Step 3: Browser Cache Test</h2>";
echo "<p>To test if this is a browser cache issue:</p>";
echo "<ol>";
echo "<li>Press <strong>Ctrl+F5</strong> (or Cmd+Shift+R on Mac) to hard refresh</li>";
echo "<li>Try opening your gallery in an <strong>incognito/private window</strong></li>";
echo "<li><strong>Clear browser cache</strong> completely</li>";
echo "</ol>";

echo "<h2>Step 4: Direct Thumbnail Test</h2>";
if ($thumbFiles > 0) {
    $sampleFile = basename($thumbFileList[0]);
    $sampleUrl = "uploads/thumbs/" . $sampleFile;
    echo "<p>Test a sample thumbnail: <a href='$sampleUrl' target='_blank'>$sampleUrl</a></p>";
    echo "<p>If the link above shows a white/blank image, the thumbnail file is corrupted or empty.</p>";
}

echo "<h2>Step 5: Recommendations</h2>";

if (empty($issues)) {
    echo "<p class='success'>✅ No major issues found with thumbnail files!</p>";
    echo "<p>The problem might be:</p>";
    echo "<ul>";
    echo "<li><strong>Browser cache</strong> - Try Ctrl+F5 hard refresh</li>";
    echo "<li><strong>Gallery code issue</strong> - Check if the gallery is using correct image paths</li>";
    echo "<li><strong>CDN or caching layer</strong> - If using Cloudflare or similar</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Found " . count($issues) . " issues:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";

    echo "<h3>Fixes Applied:</h3>";
    if (!empty($fixes)) {
        echo "<ul>";
        foreach ($fixes as $fix) {
            echo "<li class='success'>✅ $fix</li>";
        }
        echo "</ul>";
    }

    echo "<h3>Manual Fixes Needed:</h3>";
    echo "<ol>";
    if (in_array("No thumbnail files exist", $issues)) {
        echo "<li><strong>Upload images</strong> - You need to upload some photos first</li>";
        echo "<li><strong>Run thumbnail regeneration</strong> - Visit <code>regenerate_thumbnails.php</code></li>";
    }
    if (count($emptyFiles) > 0) {
        echo "<li><strong>Regenerate empty thumbnails</strong> - Run <code>regenerate_thumbnails.php</code></li>";
    }
    if (count($corruptedFiles) > 0) {
        echo "<li><strong>Regenerate corrupted thumbnails</strong> - Delete and recreate corrupted files</li>";
    }
    echo "<li><strong>Clear browser cache</strong> - Ctrl+F5 hard refresh</li>";
    echo "</ol>";
}

echo "<h2>Quick Test Links</h2>";
echo "<ul>";
echo "<li><a href='test_thumbnail_display.php'>Test Thumbnail Display</a></li>";
echo "<li><a href='debug_thumbnails.php'>Debug Thumbnails</a></li>";
echo "<li><a href='regenerate_thumbnails.php'>Regenerate Thumbnails</a></li>";
echo "<li><a href='gallery_test.php'>Test Gallery Display</a></li>";
echo "</ul>";
?>