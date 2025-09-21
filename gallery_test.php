<?php
// gallery_test.php - Test gallery thumbnail display without database
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Thumbnail Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        .photo-card { border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); background: white; }
        .photo-card img { width: 100%; height: 200px; object-fit: cover; display: block; }
        .photo-card.failed { border: 2px solid red; }
        .photo-card.failed img { opacity: 0.5; }
        .status { padding: 10px; background: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 12px; }
        .success { color: green; }
        .error { color: red; }
        h1 { text-align: center; color: #333; }
        .summary { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; max-width: 1200px; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    <h1>Gallery Thumbnail Test</h1>

    <div class="summary">
        <h2>Test Results Summary</h2>
        <div id="summary">Testing...</div>
    </div>

    <div class="gallery" id="gallery"></div>

    <script>
        // Test data - simulate what the gallery should receive
        const testPhotos = [
            { filename: 'test1.jpg', thumb: 'uploads/thumbs/test1.jpg' },
            { filename: 'test2.jpg', thumb: 'uploads/thumbs/test2.jpg' },
            { filename: 'test3.jpg', thumb: 'uploads/thumbs/test3.jpg' }
        ];

        let successCount = 0;
        let failCount = 0;

        function createPhotoCard(photo, index) {
            const card = document.createElement('div');
            card.className = 'photo-card';
            card.innerHTML = `
                <img src="${photo.thumb}" alt="${photo.filename}" onload="onImageLoad(this, ${index})" onerror="onImageError(this, ${index})">
                <div class="status" id="status-${index}">Loading...</div>
            `;
            return card;
        }

        function onImageLoad(img, index) {
            successCount++;
            const status = document.getElementById(`status-${index}`);
            status.innerHTML = '<span class="success">✅ Loaded successfully</span>';
            status.innerHTML += `<br>Size: ${img.naturalWidth}x${img.naturalHeight}`;
            updateSummary();
        }

        function onImageError(img, index) {
            failCount++;
            const card = img.parentElement;
            card.classList.add('failed');

            const status = document.getElementById(`status-${index}`);
            status.innerHTML = '<span class="error">❌ Failed to load</span>';
            status.innerHTML += `<br>URL: ${img.src}`;
            updateSummary();
        }

        function updateSummary() {
            const total = successCount + failCount;
            const summary = document.getElementById('summary');

            if (total === testPhotos.length) {
                summary.innerHTML = `
                    <p><strong>Total Images:</strong> ${total}</p>
                    <p><strong class="success">Successful:</strong> ${successCount}</p>
                    <p><strong class="error">Failed:</strong> ${failCount}</p>
                    <p><strong>Success Rate:</strong> ${Math.round((successCount/total)*100)}%</p>
                `;

                if (failCount > 0) {
                    summary.innerHTML += `
                        <p style="color: red; font-weight: bold;">❌ Some thumbnails failed to load!</p>
                        <p><strong>Possible causes:</strong></p>
                        <ul>
                            <li>Thumbnail files don't exist at the specified paths</li>
                            <li>File permissions prevent access</li>
                            <li>Wrong file paths or URLs</li>
                        </ul>
                    `;
                } else {
                    summary.innerHTML += `
                        <p style="color: green; font-weight: bold;">✅ All thumbnails loaded successfully!</p>
                        <p>The issue might be with the database or gallery data, not the thumbnail display itself.</p>
                    `;
                }
            }
        }

        // Initialize test
        const gallery = document.getElementById('gallery');
        testPhotos.forEach((photo, index) => {
            gallery.appendChild(createPhotoCard(photo, index));
        });
    </script>
</body>
</html>