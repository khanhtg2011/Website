<?php
session_start();
require __DIR__ . "/config.php";

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$isAdmin) {
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Test - Photo Gallery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        input[type="file"] {
            margin: 10px 0;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🧪 Upload Functionality Test</h1>

    <div class="test-section">
        <h2>System Status</h2>
        <div class="status info">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Upload Directory:</strong> <?php echo is_dir(__DIR__ . '/uploads') ? '✅ Exists' : '❌ Missing'; ?><br>
            <strong>Thumb Directory:</strong> <?php echo is_dir(__DIR__ . '/uploads/thumbs') ? '✅ Exists' : '❌ Missing'; ?><br>
            <strong>Database:</strong> <?php echo $conn->connect_error ? '❌ Error: ' . $conn->connect_error : '✅ Connected'; ?><br>
            <strong>Admin Session:</strong> <?php echo $isAdmin ? '✅ Active' : '❌ Not active'; ?><br>
            <strong>CSRF Token:</strong> <?php echo !empty(csrf_token()) ? '✅ Present' : '❌ Missing'; ?>
        </div>
    </div>

    <div class="test-section">
        <h2>Manual Upload Test</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" id="testFile" name="testFile" accept="image/*" required>
            <br>
            <button type="button" onclick="testUpload()">Test Upload</button>
            <button type="button" onclick="checkDatabase()">Check Database</button>
            <button type="button" onclick="clearCache()">Clear Cache</button>
        </form>

        <div id="uploadStatus"></div>
    </div>

    <div class="test-section">
        <h2>Debug Information</h2>
        <div id="debugInfo" class="debug-info">
            Click buttons above to see debug information...
        </div>
    </div>

    <div class="test-section">
        <h2>Recent Photos in Database</h2>
        <div id="recentPhotos" class="debug-info">
            Loading...
        </div>
    </div>

    <script>
        const CSRF_TOKEN = "<?php echo addslashes(htmlspecialchars(csrf_token(), ENT_QUOTES)); ?>";

        function testUpload() {
            const fileInput = document.getElementById('testFile');
            const statusDiv = document.getElementById('uploadStatus');

            if (!fileInput.files[0]) {
                statusDiv.innerHTML = '<div class="status error">Please select a file first!</div>';
                return;
            }

            const file = fileInput.files[0];
            statusDiv.innerHTML = '<div class="status info">Uploading: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)</div>';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf', CSRF_TOKEN);

            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.text();
            })
            .then(result => {
                if (result === 'OK - File uploaded successfully') {
                    statusDiv.innerHTML = '<div class="status success">✅ Upload successful! File saved to server.</div>';
                    checkDatabase(); // Refresh database info
                } else {
                    statusDiv.innerHTML = '<div class="status error">❌ Upload failed: ' + result + '</div>';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="status error">❌ Network error: ' + error.message + '</div>';
                console.error('Upload error:', error);
            });
        }

        function checkDatabase() {
            fetch('list.php?album=all&sort=date&order=desc')
            .then(response => response.json())
            .then(data => {
                const debugDiv = document.getElementById('debugInfo');
                const photosDiv = document.getElementById('recentPhotos');

                debugDiv.innerHTML = '<strong>API Response:</strong><br>' +
                    'Photos returned: ' + data.length + '<br>' +
                    'First photo: ' + (data[0] ? data[0].filename : 'None') + '<br>' +
                    'Cache used: ' + (data.length > 0 ? 'Check network tab' : 'N/A');

                if (data.length > 0) {
                    photosDiv.innerHTML = '<strong>Recent Photos:</strong><br>';
                    data.slice(0, 5).forEach((photo, index) => {
                        const fullPath = 'uploads/' + photo.filename;
                        const thumbPath = photo.thumb;
                        photosDiv.innerHTML += `${index + 1}. ${photo.filename}<br>`;
                        photosDiv.innerHTML += `   Full: ${fullPath} (${photo.size ? (photo.size / 1024).toFixed(1) + ' KB' : 'Unknown'})<br>`;
                        photosDiv.innerHTML += `   Thumb: ${thumbPath}<br>`;
                        photosDiv.innerHTML += `   Date: ${photo.date}<br><br>`;
                    });
                } else {
                    photosDiv.innerHTML = 'No photos found in database!';
                }
            })
            .catch(error => {
                document.getElementById('debugInfo').innerHTML = '❌ Error loading photos: ' + error.message;
                document.getElementById('recentPhotos').innerHTML = '❌ Could not load photo list';
            });
        }

        function clearCache() {
            // Clear local cache by making a request with cache buster
            fetch('list.php?album=all&sort=date&order=desc&t=' + Date.now())
            .then(() => {
                document.getElementById('debugInfo').innerHTML = '✅ Cache cleared (if any existed)';
                checkDatabase();
            })
            .catch(error => {
                document.getElementById('debugInfo').innerHTML = '❌ Error clearing cache: ' + error.message;
            });
        }

        // Load initial data
        window.addEventListener('DOMContentLoaded', function() {
            checkDatabase();
        });
    </script>
</body>
</html>