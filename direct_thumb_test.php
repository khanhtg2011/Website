<?php
// direct_thumb_test.php - Direct thumbnail loading test
echo "<h1>Direct Thumbnail Loading Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .thumb-container{display:inline-block;margin:10px;border:1px solid #ccc;padding:5px;} .thumb-container img{max-width:200px;max-height:200px;} .error{color:red;} .success{color:green;}</style>";

$thumbDir = __DIR__ . "/uploads/thumbs/";
$results = [];

if (is_dir($thumbDir)) {
    $files = glob($thumbDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    $files = array_slice($files, 0, 20); // Test first 20 files

    echo "<p>Testing " . count($files) . " thumbnail files...</p>";
    echo "<div style='display:flex;flex-wrap:wrap;'>";

    foreach ($files as $file) {
        $filename = basename($file);
        $webPath = "uploads/thumbs/" . $filename;
        $fileSize = filesize($file);

        echo "<div class='thumb-container'>";g
        echo "<strong>$filename</strong><br>";
        echo "<small>{$fileSize} bytes</small><br>";
        echo "<img src='$webPath?t=" . time() . "' alt='$filename' ";
        echo "onload='console.log(\"Loaded: $filename\")' ";
        echo "onerror='console.error(\"Failed: $filename\"); this.parentElement.innerHTML+=\"<br><span class=error>FAILED TO LOAD</span>\";'>";
        echo "</div>";
    }

    echo "</div>";

    echo "<h2>Test Results</h2>";
    echo "<p>Open your browser's Developer Tools (F12) → Console tab to see loading results.</p>";
    echo "<p>Each successfully loaded thumbnail will log: <code>Loaded: filename.jpg</code></p>";
    echo "<p>Each failed thumbnail will log: <code>Failed: filename.jpg</code></p>";

} else {
    echo "<p class='error'>❌ Thumbnails directory not found!</p>";
}

echo "<h2>Manual Test URLs</h2>";
echo "<p>You can also test these URLs directly in your browser:</p>";
if (isset($files) && count($files) > 0) {
    echo "<ul>";
    foreach (array_slice($files, 0, 5) as $file) {
        $filename = basename($file);
        $webPath = "uploads/thumbs/" . $filename;
        echo "<li><a href='$webPath?t=" . time() . "' target='_blank'>$webPath</a></li>";
    }
    echo "</ul>";
}

echo "<h2>Troubleshooting</h2>";
echo "<ol>";
echo "<li><strong>Check browser cache:</strong> Press Ctrl+F5 to hard refresh</li>";
echo "<li><strong>Test in incognito:</strong> Open in private/incognito window</li>";
echo "<li><strong>Check file permissions:</strong> Files should be 0644, directories 0755</li>";
echo "<li><strong>Verify file existence:</strong> Use the URLs above to test directly</li>";
echo "</ol>";
?>