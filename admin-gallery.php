<?php
session_start();
require __DIR__ . "/config.php";

// Check if user has private gallery access (either admin or password authenticated)
$hasPrivateAccess = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
                   (isset($_SESSION['private_gallery_access']) && $_SESSION['private_gallery_access'] === true);

if (!$hasPrivateAccess) {
    header("Location: /");
    exit;
}

// Set admin mode for UI if user is actually admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Visitor tracking (only for admin users in this special gallery)
$visitorsFile = __DIR__ . '/data/admin_gallery_visitors.json';
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Load existing visitor data
$visitors = [];
if (file_exists($visitorsFile)) {
    $data = file_get_contents($visitorsFile);
    $visitors = json_decode($data, true) ?: [];
}

// Get visitor information
$visitorInfo = [
    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s'),
    'page' => 'admin-gallery',
    'session_id' => session_id(),
    'admin_user' => $_SESSION['username'] ?? 'unknown'
];

// Check if this admin visit was already recorded recently (within last 5 minutes)
$recentVisit = false;
foreach ($visitors as $existingVisitor) {
    if ($existingVisitor['ip'] === $visitorInfo['ip'] &&
        $existingVisitor['session_id'] === $visitorInfo['session_id'] &&
        $existingVisitor['admin_user'] === $visitorInfo['admin_user'] &&
        (time() - strtotime($existingVisitor['timestamp'])) < 300) { // 5 minutes
        $recentVisit = true;
        break;
    }
}

// Add new admin visit if not recently recorded
if (!$recentVisit) {
    $visitors[] = $visitorInfo;

    // Clean up old entries (keep only last 100 entries)
    if (count($visitors) > 100) {
        $visitors = array_slice($visitors, -100);
    }

    // Save visitor data
    file_put_contents($visitorsFile, json_encode($visitors, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="format-detection" content="telephone=no" />
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>💝 Private Memories — Personal Vault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <style>
        :root { --green: #4caf50; --admin-red: #dc3545; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #121212; color: #eee; min-height: 100vh; width: 100%; height: 100%; }
        main, #gallery, .photo-card { background: transparent !important; }
        main { margin: 0; padding: 0; min-height: 100vh; }
        #gallery { margin: 0 auto 40px; padding: 18px; gap: 30px; grid-template-columns: repeat(3, minmax(200px, 1fr)); justify-content: center; max-width: 1400px; }

        header { position: sticky; top: 0; z-index: 10; background: linear-gradient(135deg, #2a2a2a, #1a1a1a); color: #fff; box-shadow: 0 4px 20px rgba(0, 0, 0, .3); border-bottom: 3px solid rgba(220, 53, 69, 0.3); width: 100%; margin: 0; }

        .header-main { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); max-width: 1400px; margin: 0 auto; }

        .header-main h1 { margin: 0; font-size: 22px; font-weight: 700; color: #dc3545; }

        .admin-indicator { display: flex; align-items: center; gap: 8px; background: rgba(220, 53, 69, 0.1); padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(220, 53, 69, 0.3); }
        .admin-icon { font-size: 16px; }
        .admin-text { font-weight: 600; font-size: 14px; color: #dc3545; }

        .header-controls { padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; max-width: 1400px; margin: 0 auto; }

        .admin-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .btn { background: rgba(255, 255, 255, 0.9); color: var(--admin-red); border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 14px; }
        .btn:hover { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
        .btn.primary { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .btn.primary:hover { background: linear-gradient(135deg, #c82333, #bd2130); }
        .btn.secondary { background: rgba(255, 255, 255, 0.9); color: #666; }
        .btn.secondary:hover { background: #fff; color: #333; }

        .photo-card { position: relative; border-radius: 8px; overflow: hidden; cursor: pointer; opacity: 0; transform: scale(.88); animation: fadeIn .5s forwards; background: transparent; width: 100%; max-width: 400px; height: 200px; margin: 0 auto; }
        .photo-card img { width: 100%; height: 100%; object-fit: cover; display: block; }
        @keyframes fadeIn { to { opacity: 1; transform: scale(1); } }

        /* Responsive breakpoints - Default to 3 columns */
        @media (min-width: 1200px) { #gallery { grid-template-columns: repeat(5, minmax(200px, 1fr)); } .photo-card { height: 200px; } .photo-card img { height: 200px; } }
        @media (min-width: 768px) and (max-width: 1199px) { #gallery { grid-template-columns: repeat(3, minmax(200px, 1fr)); } .photo-card { height: 200px; } .photo-card img { height: 200px; } }
        @media (max-width: 767px) { main { padding: 0 18px; } #gallery { grid-template-columns: repeat(2, minmax(160px, 1fr)); } .photo-card { height: 160px; max-width: 100%; } .photo-card img { height: 160px; } header { flex-direction: column; align-items: flex-start; gap: 10px; } .actions { justify-content: flex-start; } }
        @media (max-width: 480px) { #gallery { grid-template-columns: 1fr; } .photo-card { height: 200px; } .photo-card img { height: 200px; } }

        .delete-btn { position: absolute; top: 8px; right: 8px; background: rgba(0, 0, 0, .6); color: #fff; border: none; border-radius: 50%; padding: 6px 9px; cursor: pointer; z-index: 2; opacity: 1; transform: scale(1); pointer-events: auto; transition: opacity .25s, transform .25s; }
        .delete-btn:hover { background: rgba(0, 0, 0, .8); }

        #modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .85); justify-content: center; align-items: center; padding: 18px; animation: fadeInModal .25s; z-index: 10000; }
        #modal > div { animation: modalSlideIn .3s ease-out; }
        @keyframes modalSlideIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        #modal > div { display: flex; align-items: flex-start; gap: 20px; max-width: 95vw; max-height: 95vh; padding: 20px; }
        #modal img { max-width: 70vw; max-height: 85vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: opacity 0.3s ease, transform 0.3s ease; }
        #modalInfo { flex: 0 0 350px; background: rgba(0,0,0,0.8); padding: 25px; border-radius: 12px; color: white; transition: opacity 0.3s ease, transform 0.3s ease; backdrop-filter: blur(10px); }

        @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }

        .empty { text-align: center; opacity: .7; padding: 28px 0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state .empty-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
        .empty-state h3 { margin: 0 0 10px 0; font-size: 24px; font-weight: 600; color: #333; }
        .empty-state p { margin: 0 0 30px 0; font-size: 16px; color: #666; }

        /* Admin gallery specific styling */
        .admin-notice { background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 255, 255, 0.05)); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 8px; padding: 16px; margin: 20px auto; max-width: 800px; text-align: center; }
        .admin-notice h3 { color: #dc3545; margin: 0 0 8px 0; font-size: 18px; }
        .admin-notice p { margin: 0; color: rgba(255, 255, 255, 0.8); font-size: 14px; }

        /* Stats display */
        .gallery-stats { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        .stat-item { display: flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.1); padding: 8px 14px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.2); }
        .stat-icon { font-size: 14px; }
        .stat-count { font-weight: bold; font-size: 14px; color: #dc3545; }
        .stat-text { font-size: 12px; color: rgba(255, 255, 255, 0.8); }
    </style>
</head>
<body>
    <header>
        <div class="header-main">
            <h1>💝 Private Memories</h1>
            <div class="admin-indicator">
                <span class="admin-icon">🔒</span>
                <span class="admin-text">Personal Vault</span>
            </div>
        </div>

        <div class="header-controls">
            <div class="gallery-stats" id="galleryStats">
                <div class="stat-item">
                    <span class="stat-icon">💝</span>
                    <span class="stat-count" id="memoryCount">0</span>
                    <span class="stat-text">memories</span>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">�️</span>
                    <span class="stat-count" id="photoCount">0</span>
                    <span class="stat-text">photos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">🎥</span>
                    <span class="stat-count" id="videoCount">0</span>
                    <span class="stat-text">videos</span>
                </div>
            </div>

            <div class="admin-actions">
                <?php if ($isAdmin): ?>
                    <button class="btn primary" onclick="document.getElementById('memoryFileInput').click()">📤 Add Photo/Video</button>
                <?php endif; ?>
                <button class="btn primary" onclick="location.href='/'">← Back to Public Gallery</button>
                <?php if ($isAdmin): ?>
                    <button class="btn secondary" onclick="location.href='/admin_logout'">🚪 Logout</button>
                <?php else: ?>
                    <button class="btn secondary" onclick="location.href='/?logout_private=1'">🚪 Exit Private Gallery</button>
                <?php endif; ?>
            </div>

            <input type="file" id="memoryFileInput" accept="image/*,video/*" multiple hidden onchange="uploadMemories(this.files)">
        </div>
    </header>

    <div class="admin-notice">
        <h3>💝 Your Private Memories</h3>
        <p>This is your personal vault for cherished memories. Upload photos and videos that are special to you - completely private and secure.</p>
    </div>

    <main id="gallery"></main>

    <div id="modal">
        <div style="display: flex; align-items: flex-start; gap: 20px; max-width: 100%; max-height: 100%; padding: 20px; position: relative;">
            <button id="modalCloseBtn" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10001; transition: all 0.2s;">✕</button>
            <div id="modalInfo" style="flex: 0 0 300px; background: rgba(0,0,0,0.7); padding: 20px; border-radius: 8px; color: white;"></div>
            <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
                <img id="modalImg" src="" alt="Photo" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
            </div>
        </div>
    </div>

    <script>
        let photos = [];
        let currentPhotoIndex = -1;

        // Load private memories
        async function loadGallery() {
            try {
                console.log('🔒 Loading private memories...');

                const response = await fetch('private_memories_handler.php?action=list', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                photos = Array.isArray(data) ? data : [];

                console.log(`✅ Loaded ${photos.length} private memories`);

                // Update stats
                const photoCount = photos.filter(p => !p.is_video).length;
                const videoCount = photos.filter(p => p.is_video).length;

                document.getElementById('memoryCount').textContent = photos.length;
                document.getElementById('photoCount').textContent = photoCount;
                document.getElementById('videoCount').textContent = videoCount;

                renderGallery();
            } catch (error) {
                console.error('❌ Failed to load private memories:', error);
                document.getElementById('gallery').innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Error Loading Memories</h3><p>Unable to load your private memories. Please try again.</p></div>';
            }
        }

        function renderGallery() {
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = '';

            if (photos.length === 0) {
                gallery.innerHTML = '<div class="empty-state"><div class="empty-icon">📷</div><h3>No Photos Found</h3><p>The gallery is currently empty.</p></div>';
                return;
            }

            photos.forEach((photo, index) => {
                const card = createPhotoCard(photo, index);
                gallery.appendChild(card);
            });
        }

        function createPhotoCard(p, index) {
            const card = document.createElement("div");
            card.className = "photo-card";
            card.dataset.index = index;

            if (p.is_video) {
                // Create video thumbnail - similar to image but with play overlay
                const videoThumb = document.createElement("div");
                videoThumb.style.cssText = `
                    width: 100%;
                    height: 100%;
                    position: relative;
                    background: #f5f5f5;
                    border-radius: 8px;
                    overflow: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                `;

                // Add video file icon as placeholder
                const videoIcon = document.createElement("div");
                videoIcon.innerHTML = "🎥";
                videoIcon.style.cssText = `
                    font-size: 48px;
                    color: #666;
                `;
                videoThumb.appendChild(videoIcon);

                // Add small play icon overlay in corner
                const playIcon = document.createElement("div");
                playIcon.innerHTML = "▶️";
                playIcon.style.cssText = `
                    position: absolute;
                    bottom: 8px;
                    right: 8px;
                    background: rgba(0,0,0,0.7);
                    color: white;
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                    backdrop-filter: blur(4px);
                `;
                videoThumb.appendChild(playIcon);

                card.appendChild(videoThumb);
            } else {
                // Create image element with lazy loading
                const img = document.createElement("img");
                img.src = p.thumb || p.full_image;
                img.alt = `Memory ${index + 1}`;
                img.loading = "lazy";

                // Add error handling
                img.onerror = function() {
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
                };

                card.appendChild(img);
            }

            // Album tag if applicable
            if (p.album_name && p.album_name !== 'General') {
                const albumTag = document.createElement("div");
                albumTag.className = "album-tag";
                albumTag.textContent = p.album_name;
                card.appendChild(albumTag);
            }

            // Delete button (admin only)
            const del = document.createElement("button");
            del.className = "delete-btn";
            del.textContent = "🗑️";
            del.onclick = (e) => {
                e.stopPropagation();
                deleteSelected([p.filename]);
            };
            card.appendChild(del);

            return card;
        }

        // Delete selected memories
        window.deleteSelected = function(files) {
            if (!files.length) {
                alert("Chưa chọn kỷ niệm nào!");
                return;
            }
            if (!confirm("Xóa " + files.length + " kỷ niệm?")) return;

            // Delete files one by one
            const deletePromises = files.map(filename =>
                fetch("private_memories_handler.php?action=delete", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ filename: filename })
                }).then(r => r.json())
            );

            Promise.all(deletePromises).then(results => {
                const successCount = results.filter(r => r.success).length;
                const errorCount = results.length - successCount;

                if (successCount > 0) {
                    alert(`Đã xóa ${successCount} kỷ niệm thành công!`);
                    loadGallery(); // Reload gallery after deletion
                }

                if (errorCount > 0) {
                    alert(`Có ${errorCount} kỷ niệm xóa thất bại.`);
                }
            }).catch(error => {
                alert("Lỗi kết nối khi xóa!");
                console.error('Delete error:', error);
            });
        };

        // Modal functionality
        const modal = document.getElementById('modal');
        const modalImg = document.getElementById('modalImg');
        const modalInfo = document.getElementById('modalInfo');
        const modalCloseBtn = document.getElementById('modalCloseBtn');

        // Click on image
        document.getElementById('gallery').addEventListener("click", (e) => {
            const card = e.target.closest(".photo-card");
            if (!card || e.target.classList.contains('delete-btn')) return;

            e.preventDefault();
            currentPhotoIndex = parseInt(card.dataset.index, 10);
            modal.style.display = "flex";

            updateModalInfo(currentPhotoIndex);
        });

        // Close modal
        modalCloseBtn.addEventListener("click", () => {
            closeModal();
        });

        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modal.style.display = "none";

            // Stop any playing video
            const video = modal.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
                video.remove();
            }

            // Clear image
            modalImg.src = "";
            modalImg.style.display = 'block';
            currentPhotoIndex = -1;
        }

        // Keyboard navigation
        document.addEventListener("keydown", (e) => {
            if (modal.style.display !== "flex") return;

            if (e.key === "ArrowLeft") {
                e.preventDefault();
                if (currentPhotoIndex > 0) {
                    currentPhotoIndex--;
                    updateModalInfo(currentPhotoIndex);
                }
            } else if (e.key === "ArrowRight") {
                e.preventDefault();
                if (currentPhotoIndex < photos.length - 1) {
                    currentPhotoIndex++;
                    updateModalInfo(currentPhotoIndex);
                }
            } else if (e.key === "Escape") {
                e.preventDefault();
                closeModal();
            }
        });

        function updateModalInfo(index) {
            const photo = photos[index];

            // Clear previous content
            const modalContent = modal.querySelector('div > div:first-child');
            const existingVideo = modalContent.querySelector('video');
            if (existingVideo) {
                existingVideo.remove();
            }

            if (photo.is_video) {
                // Create video element for videos
                const video = document.createElement('video');
                video.src = photo.full_video;
                video.controls = true;
                video.style.cssText = `
                    max-width: 70vw;
                    max-height: 85vh;
                    object-fit: contain;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                    background: #000;
                `;
                video.preload = 'metadata';

                // Replace image with video
                modalImg.style.display = 'none';
                modalContent.insertBefore(video, modalContent.querySelector('div[id="modalInfo"]'));
            } else {
                // Show image for photos
                modalImg.style.display = 'block';
                modalImg.src = photo.full_image || photo.thumb;
                modalImg.alt = `Memory ${index + 1}`;
            }

            // Update info panel
            let info = `<div style="font-size: 16px; font-weight: bold; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.3);">${photo.is_video ? '🎥' : '📁'} ${photo.filename}</div>`;

            // Add metadata if available
            if (photo.metadata) {
                const md = photo.metadata;
                const cameraInfo = getCameraInfo(md);
                const lens = md.EXIF?.UndefinedTag?.[0] || "";
                const dateTaken = md.EXIF?.DateTimeOriginal || "Unknown";

                info += `
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 18px; font-weight: bold; color: #dc3545; margin-bottom: 8px;">📷 ${cameraInfo.model}</div>
                        ${lens ? `<div style="font-size: 14px; color: #81c784; font-style: italic;">🔍 ${lens}</div>` : ''}
                    </div>
                    <div style="display: grid; gap: 12px;">
                        ${cameraInfo.aperture ? `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span style="font-weight: 600; color: #ccc;">Aperture:</span><span style="font-weight: bold; color: white;">${cameraInfo.aperture}</span></div>` : ''}
                        ${cameraInfo.iso ? `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span style="font-weight: 600; color: #ccc;">ISO:</span><span style="font-weight: bold; color: white;">${cameraInfo.iso}</span></div>` : ''}
                        ${cameraInfo.focal ? `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span style="font-weight: 600; color: #ccc;">Focal:</span><span style="font-weight: bold; color: white;">${cameraInfo.focal}</span></div>` : ''}
                        ${dateTaken !== "Unknown" ? `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span style="font-weight: 600; color: #ccc;">Taken:</span><span style="font-weight: bold; color: white;">${formatDate(dateTaken)}</span></div>` : ''}
                    </div>
                `;
            }

            modalInfo.innerHTML = info;
        }

        function getCameraInfo(metadata) {
            if (!metadata) {
                return { hasInfo: true, model: 'Unknown Camera', aperture: null, focal: null, iso: null };
            }

            const exif = metadata.EXIF || {};
            const ifd0 = metadata.IFD0 || {};
            const make = exif.Make || ifd0.Make || '';
            const model = exif.Model || ifd0.Model || 'Unknown Camera';
            const cameraModel = make && make !== model ? `${make} ${model}` : model;

            let aperture = null;
            if (exif.FNumber) {
                let fNumber = exif.FNumber;
                if (typeof fNumber === 'string') {
                    if (fNumber.includes('/')) {
                        const parts = fNumber.split('/');
                        if (parts.length === 2) {
                            const numerator = parseFloat(parts[0]);
                            const denominator = parseFloat(parts[1]);
                            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                                fNumber = numerator / denominator;
                            }
                        }
                    } else if (fNumber.toLowerCase().startsWith('f')) {
                        fNumber = parseFloat(fNumber.substring(1));
                    } else {
                        fNumber = parseFloat(fNumber);
                    }
                } else if (Array.isArray(fNumber)) {
                    if (fNumber.length === 2) {
                        const numerator = fNumber[0];
                        const denominator = fNumber[1];
                        if (denominator !== 0) {
                            fNumber = numerator / denominator;
                        }
                    }
                }

                fNumber = parseFloat(fNumber);
                if (!isNaN(fNumber) && fNumber > 0) {
                    let formatted;
                    if (fNumber >= 10) {
                        formatted = Math.round(fNumber);
                    } else if (fNumber % 1 === 0) {
                        formatted = fNumber;
                    } else {
                        formatted = Math.round(fNumber * 10) / 10;
                    }
                    aperture = `f/${formatted}`;
                }
            }

            let focal = null;
            if (exif.FocalLength) {
                let focalLength = typeof exif.FocalLength === 'string' ? parseFloat(exif.FocalLength) : exif.FocalLength;
                if (focalLength > 1000) {
                    if (focalLength % 10 === 0 && focalLength / 10 <= 1000) {
                        focalLength = focalLength / 10;
                    } else if (focalLength % 100 === 0 && focalLength / 100 <= 1000) {
                        focalLength = focalLength / 100;
                    } else if (focalLength % 1000 === 0 && focalLength / 1000 <= 1000) {
                        focalLength = focalLength / 1000;
                    }
                }
                if (focalLength >= 100 && focalLength % 10 === 0) {
                    const divided = focalLength / 10;
                    if (divided >= 10 && divided <= 1000) {
                        focalLength = divided;
                    }
                }
                if (!isNaN(focalLength) && focalLength > 0 && focalLength <= 2000) {
                    const rounded = Math.round(focalLength);
                    focal = `${rounded}mm`;
                }
            }

            const iso = exif.ISOSpeedRatings || exif.ISO || exif.ISOSpeed || null;

            return { hasInfo: true, model: cameraModel, aperture, focal, iso };
        }

        function formatDate(dateString) {
            try {
                const date = new Date(dateString.replace(/:/g, '-'));
                if (isNaN(date.getTime())) {
                    return dateString;
                }
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } catch {
                return dateString;
            }
        }

        // Upload memories
        async function uploadMemories(files) {
            if (!files || files.length === 0) return;

            console.log(`📤 Uploading ${files.length} memories...`);

            const uploadPromises = Array.from(files).map(async (file) => {
                const formData = new FormData();
                formData.append('file', file);

                try {
                    const response = await fetch('private_memories_handler.php?action=upload', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();
                    if (result.success) {
                        console.log(`✅ Uploaded: ${file.name}`);
                        return { success: true, file: file.name };
                    } else {
                        console.error(`❌ Failed to upload ${file.name}:`, result.error);
                        return { success: false, file: file.name, error: result.error };
                    }
                } catch (error) {
                    console.error(`❌ Network error uploading ${file.name}:`, error);
                    return { success: false, file: file.name, error: 'Network error' };
                }
            });

            const results = await Promise.all(uploadPromises);
            const successCount = results.filter(r => r.success).length;
            const errorCount = results.length - successCount;

            // Show results
            if (successCount > 0) {
                alert(`✅ Successfully uploaded ${successCount} memories!`);
            }

            if (errorCount > 0) {
                const failedFiles = results.filter(r => !r.success).map(r => r.file).join(', ');
                alert(`❌ Failed to upload ${errorCount} files: ${failedFiles}`);
            }

            // Clear file input
            document.getElementById('memoryFileInput').value = '';

            // Reload gallery to show new memories
            if (successCount > 0) {
                loadGallery();
            }
        }

        // Initialize
        loadGallery();
    </script>
</body>
</html>