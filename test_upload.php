<?php
// Simple test upload script
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testfile'])) {
    $start = microtime(true);

    $file = $_FILES['testfile'];
    $filename = basename($file['name']);
    $target = __DIR__ . '/uploads/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $total = microtime(true) - $start;
        echo "SUCCESS: File uploaded in " . round($total, 3) . " seconds\n";
        echo "File: $filename\n";
        echo "Size: " . filesize($target) . " bytes\n";
    } else {
        echo "ERROR: Failed to upload file\n";
    }
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <h1>Test Upload</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="testfile" required>
        <button type="submit">Upload Test</button>
    </form>
</body>
</html>
<?php
}
?>