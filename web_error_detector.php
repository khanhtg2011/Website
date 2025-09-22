<?php
/**
 * Web Error Detection Tool
 * Detects common errors in the photo gallery web application
 */

// Enable error reporting for detection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Web Error Detector - Photo Gallery</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4caf50; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #4caf50; background: #f9f9f9; }
        .error { color: #d32f2f; font-weight: bold; }
        .warning { color: #f57c00; font-weight: bold; }
        .success { color: #388e3c; font-weight: bold; }
        .info { color: #1976d2; font-weight: bold; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .status-ok { color: #388e3c; }
        .status-error { color: #d32f2f; }
        .status-warning { color: #f57c00; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Web Error Detection Tool</h1>
        <p>This tool scans the photo gallery application for common errors and issues.</p>
";

$errors = [];
$warnings = [];
$info = [];

// 1. Check PHP version
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.0', '<')) {
    $warnings[] = "PHP version $phpVersion is outdated. Recommended: PHP 8.0+";
} else {
    $info[] = "PHP version: $phpVersion ✓";
}

// 2. Check database connection
echo "<div class='section'>
    <h2>Database Connection Check</h2>
";

try {
    require_once __DIR__ . '/config.php';

    if (!isset($conn) || !$conn) {
        $errors[] = "Database connection variable not set or null";
    } else {
        // Try to ping the database
        if ($conn->ping()) {
            $info[] = "Database connection: Connected ✓";
        } else {
            $errors[] = "Database connection: Ping failed";
        }

        // Check if tables exist
        $tables = ['photos', 'albums', 'photo_feedback'];
        foreach ($tables as $table) {
            if (DB_TYPE === 'sqlite') {
                $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
                $exists = $result && $result->fetchColumn();
            } else {
                $result = $db->query("SHOW TABLES LIKE '$table'");
                $exists = $result && $result->num_rows > 0;
            }

            if ($exists) {
                $info[] = "Table '$table': Exists ✓";
            } else {
                $warnings[] = "Table '$table': Not found";
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Database connection error: " . $e->getMessage();
}

echo "</div>";

// 3. Check file permissions
echo "<div class='section'>
    <h2>File Permissions Check</h2>
    <table>
        <tr><th>File/Directory</th><th>Permissions</th><th>Status</th></tr>
";

$filesToCheck = [
    'uploads/' => 'Directory for photo uploads',
    'config.php' => 'Database configuration',
    'index.php' => 'Main application file',
    'list.php' => 'Photo listing API',
    'upload.php' => 'Upload handler'
];

foreach ($filesToCheck as $file => $description) {
    $fullPath = __DIR__ . '/' . $file;
    $status = 'OK';
    $statusClass = 'status-ok';

    if (!file_exists($fullPath)) {
        $status = 'Not found';
        $statusClass = 'status-error';
        $errors[] = "$file: File does not exist";
    } else {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);

        if (is_dir($fullPath)) {
            // Check if directory is writable
            if (!is_writable($fullPath)) {
                $status = 'Not writable';
                $statusClass = 'status-error';
                $errors[] = "$file: Directory not writable";
            }
        } else {
            // Check if file is readable
            if (!is_readable($fullPath)) {
                $status = 'Not readable';
                $statusClass = 'status-error';
                $errors[] = "$file: File not readable";
            }
        }
    }

    echo "<tr><td>$file<br><small>$description</small></td><td>$perms</td><td class='$statusClass'>$status</td></tr>";
}

echo "</table></div>";

// 4. Check required PHP extensions
echo "<div class='section'>
    <h2>PHP Extensions Check</h2>
    <table>
        <tr><th>Extension</th><th>Status</th><th>Purpose</th></tr>
";

$requiredExtensions = [
    'pdo' => 'Database connectivity',
    'pdo_mysql' => 'MySQL database support',
    'gd' => 'Image processing',
    'mbstring' => 'Multibyte string support',
    'json' => 'JSON handling',
    'fileinfo' => 'File type detection'
];

foreach ($requiredExtensions as $ext => $purpose) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? 'Loaded ✓' : 'Not loaded';
    $statusClass = $loaded ? 'status-ok' : 'status-error';

    if (!$loaded) {
        $errors[] = "PHP extension '$ext' not loaded: $purpose";
    }

    echo "<tr><td>$ext</td><td class='$statusClass'>$status</td><td>$purpose</td></tr>";
}

echo "</table></div>";

// 5. Check configuration issues
echo "<div class='section'>
    <h2>Configuration Check</h2>
";

if (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';

    // Check database status
    if (DB_AVAILABLE) {
        $info[] = "Database connection: " . DB_TYPE . " ✓";
    } else {
        $errors[] = "Database connection: Failed";
    }

    // Check for SQLite database file
    if (DB_TYPE === 'sqlite') {
        $sqliteFile = __DIR__ . '/photos.db';
        if (file_exists($sqliteFile)) {
            $info[] = "SQLite database file exists ✓";
        } else {
            $warnings[] = "SQLite database file not found";
        }
    }

    $configContent = file_get_contents(__DIR__ . '/config.php');

    // Check for demo mode
    if (strpos($configContent, 'demo mode') !== false || strpos($configContent, 'DEMO_MODE') !== false) {
        $warnings[] = "Application appears to be in demo mode";
    }

    // Check for hardcoded credentials (basic check)
    if (preg_match('/password.*=.*["\'][^"\']*["\']/', $configContent)) {
        $warnings[] = "Potential hardcoded password found in config.php";
    }

    $info[] = "Configuration file exists and is readable ✓";
} else {
    $errors[] = "config.php file not found";
}

// 6. Test API endpoints
echo "<div class='section'>
    <h2>API Endpoints Test</h2>
";

$endpoints = [
    'list.php?album=all&limit=1' => 'Photo list API',
    'albums.php' => 'Albums API'
];

foreach ($endpoints as $endpoint => $description) {
    $url = "http://localhost:8000/$endpoint";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => 'X-Requested-With: XMLHttpRequest'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    $httpCode = isset($http_response_header) ? explode(' ', $http_response_header[0])[1] : 'unknown';

    if ($response !== false && $httpCode == '200') {
        $info[] = "$description: OK (HTTP $httpCode) ✓";
    } else {
        $errors[] = "$description: Failed (HTTP $httpCode)";
    }
}

echo "</div>";

// 7. Summary
echo "<div class='section'>
    <h2>Summary</h2>
";

$totalIssues = count($errors) + count($warnings);

if ($totalIssues == 0) {
    echo "<p class='success'>🎉 No errors detected! Your application appears to be running correctly.</p>";
} else {
    echo "<p class='warning'>⚠️ Found $totalIssues issue(s) that need attention:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>❌ $error</li>";
    }
    foreach ($warnings as $warning) {
        echo "<li class='warning'>⚠️ $warning</li>";
    }
    echo "</ul>";
}

if (!empty($info)) {
    echo "<p class='info'>ℹ️ Additional information:</p>";
    echo "<ul>";
    foreach ($info as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul>";
}

echo "</div>";

// 8. Recommendations
if ($totalIssues > 0) {
    echo "<div class='section'>
        <h2>Recommendations</h2>
        <ul>";

    if (in_array('Database connection failed', array_map(function($e) { return explode(': ', $e)[0]; }, $errors))) {
        echo "<li><strong>Database Issues:</strong> Check your database server is running and credentials in config.php are correct</li>";
    }

    if (strpos(implode(' ', $errors), 'permission') !== false) {
        echo "<li><strong>File Permissions:</strong> Ensure web server has read/write access to necessary directories</li>";
    }

    if (strpos(implode(' ', $errors), 'extension') !== false) {
        echo "<li><strong>PHP Extensions:</strong> Install missing PHP extensions using your package manager</li>";
    }

    echo "<li><strong>Logs:</strong> Check PHP error logs for additional details</li>";
    echo "<li><strong>Browser Console:</strong> Open browser developer tools to check for JavaScript errors</li>";

    echo "</ul></div>";
}

echo "
    </div>
</body>
</html>";
?>