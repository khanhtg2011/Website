<?php
session_start(); // Khởi tạo session
require __DIR__ . "/config.php";
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Xử lý logic dựa trên URI
$request = trim($_SERVER['REQUEST_URI'], '/');

// Handle AJAX login requests
if (isset($_GET['admin_login_ajax']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $ADMIN_USER = "Khanh";
    $ADMIN_PASS = "0799102011";

    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        $_SESSION['username'] = $user;
        $_SESSION['login_attempts'] = 0;
        echo json_encode(['success' => true]);
    } else {
        $_SESSION['login_attempts']++;
        $attempts_left = 3 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['login_attempts'] = 0;
            $attempts_left = 3;
        }

        echo json_encode([
            'success' => false,
            'error' => "Sai tài khoản hoặc mật khẩu! (Lần " . $_SESSION['login_attempts'] . "/3)",
            'attempts_left' => $attempts_left
        ]);
    }
    exit;
}

// Handle regular admin login page (fallback)
if ($request === 'admin_login' && !isset($_SESSION['is_admin'])) {
  // Admin login logic
  $ADMIN_USER = "Khanh";
  $ADMIN_PASS = "0799102011";
  if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
  $error = "";
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $user = trim($_POST['username'] ?? '');
      $pass = $_POST['password'] ?? '';
      if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
          $_SESSION['is_admin'] = true;
          $_SESSION['username'] = $user;
          $_SESSION['login_attempts'] = 0;
          header("Location: /");
          exit;
      } else {
          $_SESSION['login_attempts']++;
          if ($_SESSION['login_attempts'] >= 3) {
              $_SESSION['login_attempts'] = 0;
              header("Location: /");
              exit;
          }
          $error = "Sai tài khoản hoặc mật khẩu! (Lần ".$_SESSION['login_attempts']."/3)";
      }
  }
  $attempts_left = 3 - ($_SESSION['login_attempts'] ?? 0);

  // Output login page HTML
  echo '<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Login — Photo Gallery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg1:#0f172a; --bg2:#0b1220; --accent:#10b981;
  --card:#0b1220c9; --glass: rgba(255,255,255,0.06);
}
*{box-sizing:border-box}
body{
  margin:0; min-height:100vh; font-family:Inter,system-ui,Arial;
  background: radial-gradient(1200px 600px at 10% 20%, rgba(16,185,129,0.08), transparent 8%),
              radial-gradient(1000px 500px at 90% 80%, rgba(59,130,246,0.04), transparent 8%),
              linear-gradient(180deg,var(--bg1), var(--bg2));
  color:#e6eef8;
  display:flex; align-items:center; justify-content:center;
  padding:32px;
  overflow:hidden;
}
.orb{ position:absolute; border-radius:50%; filter:blur(36px); opacity:.18; }
.orb.one{ width:420px;height:420px; left:-80px; top:-120px; background:linear-gradient(90deg,#06b6d4,#3b82f6); animation: floaty 8s ease-in-out infinite; }
.orb.two{ width:300px;height:300px; right:-100px; bottom:-140px; background:linear-gradient(90deg,#06b6d4,#10b981); animation: floaty2 9s ease-in-out infinite; }
@keyframes floaty { 0%{transform:translateY(0) rotate(0deg)} 50%{transform:translateY(18px) rotate(8deg)} 100%{transform:translateY(0) rotate(0deg)} }
@keyframes floaty2{ 0%{transform:translateY(0) rotate(0deg)} 50%{transform:translateY(-18px) rotate(-6deg)} 100%{transform:translateY(0) rotate(0deg)} }
.wrap{
  width:100%; max-width:980px; display:grid; grid-template-columns: 420px 1fr; gap:28px;
  align-items:center; z-index:2;
}
.left{
  background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
  border-radius:16px; padding:28px; min-height:420px; display:flex; flex-direction:column; gap:18px;
  box-shadow: 0 10px 40px rgba(2,6,23,0.6), inset 0 1px 0 rgba(255,255,255,0.02);
  backdrop-filter: blur(6px);
}
.brand { display:flex; gap:12px; align-items:center; }
.logo{
  width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#34d399,#06b6d4);
  display:flex;align-items:center;justify-content:center;font-weight:800;color:#04212a;font-size:20px;box-shadow:0 6px 18px rgba(16,185,129,0.12);
}
h1{ margin:0; font-size:20px; letter-spacing:-0.2px;}
p.lead{ margin:0; color:rgba(230,238,248,0.85); opacity:.95; font-size:14px; line-height:1.5; }
.card{
  background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border-radius:14px; padding:26px; box-shadow: 0 12px 40px rgba(2,6,23,0.6); width:100%;
  display:flex;flex-direction:column; gap:12px; min-height:300px;
  border:1px solid rgba(255,255,255,0.03);
}
label{ font-size:13px; color:rgba(230,238,248,0.85); margin-bottom:6px; display:block; }
.field{ display:flex; flex-direction:column; gap:8px; margin-bottom:8px; }
input[type="text"], input[type="password"]{
  background:transparent; border:1px solid rgba(255,255,255,0.06); padding:12px 14px; border-radius:10px; color: #e6eef8;
  outline:none; font-size:15px; transition: box-shadow .18s, transform .12s;
}
input:focus{ box-shadow: 0 6px 18px rgba(16,185,129,0.09); transform:translateY(-2px); border-color: rgba(16,185,129,0.4); }
.row { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-top:6px; }
button.primary{
  background: linear-gradient(90deg,var(--accent), #06b6d4); color:#042a24; font-weight:700;
  padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-size:15px;
  transition: transform .12s, box-shadow .12s;
}
button.primary:hover{ transform:translateY(-3px); box-shadow: 0 12px 30px rgba(16,185,129,0.12); }
.muted{ color:rgba(230,238,248,0.65); font-size:13px; }
.error-box{
  background: linear-gradient(90deg, rgba(220,38,38,0.08), rgba(255,255,255,0.01));
  border: 1px solid rgba(220,38,38,0.13);
  color:#ffdede; padding:10px 12px; border-radius:10px; font-size:13px;
  display:flex; align-items:center; gap:10px; animation: shake .6s;
}
@keyframes shake {
  10% { transform: translateX(-6px); }
  30% { transform: translateX(6px); }
  50% { transform: translateX(-4px); }
  70% { transform: translateX(4px); }
  100% { transform: translateX(0); }
}
.tiny{ font-size:12px; color:rgba(230,238,248,0.6); }
footer.small{ margin-top:10px; font-size:12px; color:rgba(230,238,248,0.45); }
@media (max-width:920px){
  .wrap{ grid-template-columns:1fr; padding:18px; }
  .left{ order:2 }
  .card{ order:1 }
}
</style>
</head>
<body>
<div class="orb one"></div>
<div class="orb two"></div>
<div class="wrap">
  <div class="left">
    <a href="/" class="back-btn" style="display:block; margin-bottom:16px; color:#10b981; text-decoration:none; font-size:14px; font-weight:500;">
      ← Back to Gallery
    </a>
    <div class="brand">
      <div class="logo">PG</div>
      <div>
        <h1>Photo Gallery Admin</h1>
        <div class="tiny">Quản lý ảnh — upload / xóa / xem log</div>
      </div>
    </div>
    <p class="lead">Đăng nhập bằng tài khoản Admin để bật chế độ quản trị: upload ảnh, xóa ảnh và xem thông tin. Sai mật khẩu 3 lần sẽ chuyển về trang chính.</p>
    <div style="margin-top:auto;">
      <div class="tiny">Bảo mật:</div>
      <ul style="margin:8px 0 0 18px; padding:0; color:rgba(230,238,248,0.6);">
        <li>Server-side authentication</li>
        <li>Không lưu mật khẩu trên client</li>
        <li>Giới hạn số lần đăng nhập</li>
      </ul>
    </div>
  </div>
  <div class="card" role="region" aria-label="Admin login card">';
  if($error) {
      echo '<div class="error-box" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17h.01" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.29 3h3.42l7 12.12A2 2 0 0 1 19.7 19H4.3a2 2 0 0 1-1.01-3.88L10.29 3z" stroke="#ffb4b4" stroke-width="0" fill="rgba(220,38,38,0.14)"/></svg>
        <div>' . htmlspecialchars($error) . '</div>
      </div>';
  }
  echo '<form method="post" style="display:flex; flex-direction:column; gap:12px;" onsubmit="submitBtn.disabled=true;">
      <div>
        <label for="username">Tên đăng nhập</label>
        <input id="username" name="username" autocomplete="username" type="text" placeholder="admin" required>
      </div>
      <div>
        <label for="password">Mật khẩu</label>
        <input id="password" name="password" autocomplete="current-password" type="password" placeholder="••••••••" required>
      </div>
      <div class="row">
         <div class="muted tiny">Lần thử còn lại: <strong>' . $attempts_left . '</strong></div>
         <button id="submitBtn" class="primary" type="submit">Đăng nhập</button>
       </div>
      <div class="row" style="margin-top:6px;">
        <div class="tiny">Phiên bản: <strong>1.0</strong></div>
        <div class="tiny">Time: ' . date('Y-m-d H:i') . '</div>
      </div>
    </form>
  </div>
</div>
<script>
const pwd = document.getElementById(\'password\');
const usr = document.getElementById(\'username\');
const submitBtn = document.getElementById(\'submitBtn\');
pwd.addEventListener(\'keydown\', (e) => {
  if (e.key === \'Enter\') {
    e.preventDefault();
    submitBtn.click();
  }
});
if (document.querySelector(\'.error-box\')) {
  try { navigator.vibrate && navigator.vibrate(100); } catch(e){}
  const u = document.getElementById(\'username\');
  const p = document.getElementById(\'password\');
  u.classList.add(\'shake\'); p.classList.add(\'shake\');
  setTimeout(()=>{ u.classList.remove(\'shake\'); p.classList.remove(\'shake\'); }, 700);
}
</script>
</body>
</html>';
  exit;
} elseif ($request === 'admin_logout' && $isAdmin) {
    session_unset();
    session_destroy();
    header("Location: /");
    exit;
} elseif ($request === 'list' && $isAdmin) {
    header('Content-Type: application/json');
    include __DIR__ . '/list.php';
    exit;
}
?>
<script>
// Right-click toggle functionality for admin - declare globally
let rightClickEnabled = localStorage.getItem('rightClickEnabled') !== 'false'; // Default to true

(function(){
  const interactiveSelector = 'input, textarea, select, button, a, [contenteditable], .allow-select';

  // Prevent the browser starting a text selection
  document.addEventListener('selectstart', function(e) {
    if (e.target && e.target.closest && e.target.closest(interactiveSelector)) return; // allow in inputs etc
    e.preventDefault();
  }, { passive: false });

  // Prevent image/file dragging start
  document.addEventListener('dragstart', function(e) {
    if (e.target && e.target.closest && e.target.closest(interactiveSelector)) return;
    e.preventDefault();
  }, { passive: false });

  // Prevent double-click selection: block double click default behaviour
  document.addEventListener('dblclick', function(e) {
    if (e.target && e.target.closest && e.target.closest(interactiveSelector)) return;
    e.preventDefault();
  }, { passive: false });

  // Block multi-click (click-and-hold producing selection) by preventing mousedown when detail>1
  document.addEventListener('mousedown', function(e) {
    if (e.detail > 1 && e.target && e.target.closest && !e.target.closest(interactiveSelector)) {
      e.preventDefault();
    }
  }, { passive: false });

  // Enhanced right-click and long-press handling for mobile and desktop
  let longPressTimer;
  let isLongPress = false;
  let touchStartX, touchStartY;
  let contextMenuElement = null;

  // Function to detect if device is touch-enabled
  function isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  }

  // Function to show context menu for admin users
  function showAdminContextMenu(e, targetElement) {
    // Remove any existing context menu
    hideContextMenu();

    // Only show for admin users
    if (!document.body.classList.contains('admin')) {
      return;
    }

    // Create context menu
    const menu = document.createElement('div');
    menu.className = 'admin-context-menu';
    menu.style.cssText = `
      position: fixed;
      top: ${e.clientY || e.touches[0].clientY}px;
      left: ${e.clientX || e.touches[0].clientX}px;
      background: rgba(0, 0, 0, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      padding: 8px 0;
      z-index: 10000;
      min-width: 150px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(10px);
    `;

    // Add menu items based on target
    if (targetElement.closest('.photo-card')) {
      const card = targetElement.closest('.photo-card');
      const photoIndex = parseInt(card.dataset.index);
      const photo = photos[photoIndex];

      menu.innerHTML = `
        <div class="context-menu-item" onclick="downloadPhoto('${photo.filename}')" style="padding: 10px 15px; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
          <span>⬇️</span> Download
        </div>
        <div class="context-menu-item" onclick="deleteSelected(['${photo.filename}'])" style="padding: 10px 15px; color: #ff6b6b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
          <span>🗑️</span> Delete
        </div>
      `;
    } else {
      // General admin menu
      menu.innerHTML = `
        <div class="context-menu-item" onclick="showLoginModal()" style="padding: 10px 15px; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
          <span>🔐</span> Admin Panel
        </div>
      `;
    }

    document.body.appendChild(menu);
    contextMenuElement = menu;

    // Position menu within viewport
    const rect = menu.getBoundingClientRect();
    if (rect.right > window.innerWidth) {
      menu.style.left = (window.innerWidth - rect.width - 10) + 'px';
    }
    if (rect.bottom > window.innerHeight) {
      menu.style.top = (window.innerHeight - rect.height - 10) + 'px';
    }

    // Add hover effects
    menu.querySelectorAll('.context-menu-item').forEach(item => {
      item.addEventListener('mouseenter', () => {
        item.style.background = 'rgba(255, 255, 255, 0.1)';
      });
      item.addEventListener('mouseleave', () => {
        item.style.background = 'transparent';
      });
    });

    // Close menu when clicking outside
    setTimeout(() => {
      document.addEventListener('click', hideContextMenu, { once: true });
      document.addEventListener('touchstart', hideContextMenu, { once: true });
    }, 10);
  }

  // Function to hide context menu
  function hideContextMenu() {
    if (contextMenuElement) {
      contextMenuElement.remove();
      contextMenuElement = null;
    }
  }

  // Handle right-click (desktop)
  document.addEventListener('contextmenu', function(e) {
    if (e.target.closest && e.target.closest(interactiveSelector)) {
      return; // Allow context menu on interactive elements
    }

    // In admin mode, allow default browser context menu (including Inspect)
    if (document.body.classList.contains('admin')) {
      // Don't prevent default - allow browser's inspect menu
      return;
    } else {
      // For non-admin users, check toggle state
      if (rightClickEnabled) {
        // Right-click is enabled for users - allow default context menu
        return;
      } else {
        // Right-click is disabled for users - show custom context menu
        e.preventDefault();
        showAdminContextMenu(e, e.target);
      }
    }
  }, { passive: false });

  // Handle touch start for long-press detection
  document.addEventListener('touchstart', function(e) {
    if (e.touches.length !== 1) return; // Only handle single touch

    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;

    // Clear any existing timer
    clearTimeout(longPressTimer);

    // Start long-press timer (600ms)
    longPressTimer = setTimeout(() => {
      isLongPress = true;

      // Provide haptic feedback if available
      if (navigator.vibrate) {
        navigator.vibrate(50);
      }

      // Show context menu
      const fakeEvent = {
        clientX: touchStartX,
        clientY: touchStartY,
        touches: [{ clientX: touchStartX, clientY: touchStartY }]
      };
      showAdminContextMenu(fakeEvent, e.target);

      e.preventDefault();
    }, 600);
  }, { passive: false });

  // Handle touch move - cancel long-press if finger moves too much
  document.addEventListener('touchmove', function(e) {
    if (!longPressTimer) return;

    const touch = e.touches[0];
    const deltaX = Math.abs(touch.clientX - touchStartX);
    const deltaY = Math.abs(touch.clientY - touchStartY);

    // Cancel long-press if moved more than 10px
    if (deltaX > 10 || deltaY > 10) {
      clearTimeout(longPressTimer);
      longPressTimer = null;
      isLongPress = false;
    }
  }, { passive: false });

  // Handle touch end - prevent click if it was a long-press
  document.addEventListener('touchend', function(e) {
    clearTimeout(longPressTimer);
    longPressTimer = null;

    if (isLongPress) {
      e.preventDefault();
      isLongPress = false;
    }
  }, { passive: false });

  // Handle touch cancel
  document.addEventListener('touchcancel', function(e) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
    isLongPress = false;
  }, { passive: false });

})();
</script>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <meta name="format-detection" content="telephone=no" />
  <link rel="icon" type="image/png" href="/favicon.png">
  <title>📸 Khanhs Photos Gallery</title>
  <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-XXXXXX');</script>
  <!-- End Google Tag Manager -->
  <style>
    :root { --green: #4caf50; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #fafafa; color: #222;
                 transition: background .3s, color .3s; min-height: 100vh; width: 100%; }
    body.dark { background: #121212; color: #eee; }

    /* Ensure all main containers have transparent backgrounds */
    main, #gallery, .photo-card { background: transparent !important; }

    /* Make sure the layout extends to full width */
    * { box-sizing: border-box; }

    header { position: sticky; top: 0; z-index: 10;
             background: var(--green); color: #fff;
             box-shadow: 0 4px 20px rgba(0, 0, 0, .2);
             border-bottom: 3px solid rgba(255, 255, 255, 0.1); }
    body.dark header { background: linear-gradient(135deg, #2a2a2a, #1a1a1a); }

    .header-main { display: flex; justify-content: space-between; align-items: center;
                   padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .header-main h1 { margin: 0; font-size: 22px; font-weight: 700; }

    .album-navigation { display: flex; align-items: center; gap: 16px;
                       background: rgba(255, 255, 255, 0.1); padding: 10px 18px;
                       border-radius: 12px; margin-left: auto; border: 1px solid rgba(255, 255, 255, 0.2); }

    .album-indicator { display: flex; align-items: center; gap: 8px; }
    .album-icon { font-size: 18px; }
    .album-text { font-weight: 600; font-size: 14px; color: #fff; }

    .album-stats { display: flex; align-items: center; gap: 6px;
                   background: rgba(255, 255, 255, 0.2); padding: 6px 12px;
                   border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.3); }
    .stats-icon { font-size: 14px; }
    .stats-count { font-weight: bold; font-size: 14px; color: #4caf50; }
    .stats-text { font-size: 12px; color: rgba(255, 255, 255, 0.8); }
    .album-select { background: rgba(255, 255, 255, 0.95); color: #333;
                    border: 2px solid rgba(255, 255, 255, 0.4); border-radius: 8px;
                    padding: 10px 14px; font-size: 16px; font-weight: 600;
                    min-width: 220px; cursor: pointer; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease; }
    .album-select:hover { border-color: #fff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
    .album-select:focus { outline: none; border-color: #4caf50; box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3); }
    .album-select option { padding: 8px; }

    .header-controls { padding: 12px 20px; display: flex; justify-content: space-between;
                       align-items: center; flex-wrap: wrap; gap: 12px; }

    .search-sort { display: flex; gap: 12px; align-items: center; }
    .search-sort input { padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.3);
                         background: rgba(255, 255, 255, 0.9); color: #333; font-size: 14px; }
    .search-sort select { padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.3);
                          background: rgba(255, 255, 255, 0.9); color: #333; font-size: 14px; cursor: pointer; }

    .admin-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .btn { background: rgba(255, 255, 255, 0.9); color: var(--green); border: none;
           padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600;
           transition: all 0.2s; font-size: 14px; }
    .btn:hover { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
    .btn.primary { background: linear-gradient(135deg, #4caf50, #45a049); color: white; }
    .btn.primary:hover { background: linear-gradient(135deg, #45a049, #3d8b40); }
    .btn.secondary { background: rgba(255, 255, 255, 0.9); color: #666; }
    .btn.secondary:hover { background: #fff; color: #333; }
    .btn.danger { background: linear-gradient(135deg, #f44336, #d32f2f); color: white; }
    .btn.danger:hover { background: linear-gradient(135deg, #d32f2f, #b71c1c); }
    .btn.logout, .btn.login { background: rgba(255, 255, 255, 0.9); color: #666; text-decoration: none; display: inline-block; }
    .btn.logout:hover, .btn.login:hover { background: #fff; color: #333; }
    .btn { background: #fff; color: var(--green); border: none;
           padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 600;
           transition: background .2s, transform .05s; }
    .btn:hover { background: #f3f3f3; }
    .btn.ghost { background: transparent; color: #fff; border: 1px solid rgba(255, 255, 255, .6); }
    .btn.ghost:hover { background: rgba(255, 255, 255, .15); }
    .btn.toggle {
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      padding: 6px 12px;
      min-width: 120px;
      justify-content: center;
    }
    .btn.toggle:hover {
      background: linear-gradient(135deg, #ee5a52, #dc4545);
      transform: translateY(-1px);
    }
    .btn.toggle.active {
      background: linear-gradient(135deg, #4caf50, #45a049);
    }
    .btn.toggle.active:hover {
      background: linear-gradient(135deg, #45a049, #3d8b40);
    }
    body.dark .btn { background: #555; color: #fff; }
    body.dark .btn:hover { background: #666; }
    #searchInput, #sortSelect { padding: 8px; border-radius: 8px; border: none; }

    #progressWrapper { width: min(900px, 92%); margin: 14px auto 0;
                       background: #e9e9e9; border-radius: 10px;
                       display: none; height: 10px; overflow: hidden; }
    #progressBar { height: 100%; width: 0%;
                   background: linear-gradient(90deg, var(--green), #81c784);
                   transition: width .2s; }
    #uploadStatus { text-align: center; margin: 10px auto; display: none; }

    #storageInfo { margin: 10px auto 0; width: fit-content;
                    background: rgba(255, 255, 255, 0.9); color: #333;
                    padding: 6px 12px; border-radius: 8px;
                    box-shadow: 0 2px 6px rgba(0, 0, 0, .15);
                    font-size: 13px; display: flex; gap: 8px; }
    body.dark #storageInfo { background: rgba(30, 30, 30, 0.9); color: #eee; }

    #spinner { display: none; text-align: center; padding: 28px; }
    .loader { border: 6px solid #f3f3f3; border-top: 6px solid var(--green);
              border-radius: 50%; width: 40px; height: 40px;
              animation: spin 1s linear infinite; margin: auto; }
    @keyframes spin { to { transform: rotate(360deg); } }

    #gallery { display: grid; grid-template-columns: repeat(4, 1fr);
                gap: 20px; padding: 18px; max-width: 1200px; margin: 0 auto 40px;
                background: transparent; }
    .photo-card { position: relative; border-radius: 8px; overflow: hidden;
                  cursor: pointer; opacity: 0; transform: scale(.88);
                  animation: fadeIn .5s forwards; background: transparent; }
    .photo-card img { width: 100%; height: 200px; object-fit: cover;
                      display: block; }
    @keyframes fadeIn { to { opacity: 1; transform: scale(1); } }

    /* Responsive breakpoints */
    @media (min-width: 1200px) {
      #gallery { grid-template-columns: repeat(4, 1fr); }
      .photo-card img { height: 200px; }
    }
    @media (min-width: 768px) and (max-width: 1199px) {
      #gallery { grid-template-columns: repeat(3, 1fr); }
      .photo-card img { height: 180px; }
    }
    @media (max-width: 767px) {
      #gallery { grid-template-columns: repeat(2, 1fr); }
      .photo-card img { height: 160px; }
      header { flex-direction: column; align-items: flex-start; gap: 10px; }
      .actions { justify-content: flex-start; }

      /* Enhanced mobile touch improvements */
      .photo-card {
        cursor: pointer;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0.1);
      }

      .photo-card:active {
        transform: scale(0.98);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      }

      /* Better touch targets for mobile */
      .header-main {
        padding: 12px 16px;
      }

      .header-controls {
        padding: 8px 16px;
      }

      /* Improve modal on mobile */
      #modal > div {
        padding: 12px;
        gap: 12px;
      }

      #modalInfo {
        max-height: 40vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
      }

      /* Better button sizes for touch */
      .btn {
        min-height: 44px;
        padding: 12px 16px;
        font-size: 16px; /* Prevent zoom on iOS */
      }

      /* Improve search and select inputs */
      .search-sort input,
      .search-sort select {
        padding: 12px 14px;
        font-size: 16px;
        min-height: 44px;
        border-radius: 8px;
      }

      .album-select {
        padding: 12px 14px;
        min-height: 44px;
        font-size: 16px;
      }
    }
    @media (max-width: 480px) {
      #gallery { grid-template-columns: repeat(1, 1fr); }
      .photo-card img { height: 200px; }
    }

    .select-photo {
      position: absolute; top: 6px; left: 6px; z-index: 3;
      transform: scale(1.4); background: rgba(255, 255, 255, 0.8);
      padding: 2px; border-radius: 4px;
    }

    .delete-btn { position: absolute; top: 8px; right: 8px;
                  background: rgba(0, 0, 0, .6); color: #fff;
                  border: none; border-radius: 50%; padding: 6px 9px;
                  cursor: pointer; z-index: 2;
                  opacity: 0; transform: scale(.6); pointer-events: none;
                  transition: opacity .25s, transform .25s; }
    .delete-btn:hover { background: rgba(0, 0, 0, .8); }
    body.admin .delete-btn { opacity: 1; transform: scale(1); pointer-events: auto; }

    #modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .85);
              justify-content: center; align-items: center; padding: 18px;
              animation: fadeInModal .25s; z-index: 10000; }
    #modal > div { display: flex; align-items: flex-start; gap: 20px; max-width: 100%; max-height: 100%; padding: 20px; }
    #modal img { max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
    #modalInfo { flex: 0 0 300px; background: rgba(0,0,0,0.7); padding: 20px; border-radius: 8px; color: white; }

    /* Download button styling */
    #modalInfo button {
      transition: all 0.2s ease;
    }

    #modalInfo button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    #modalInfo button:active {
      transform: translateY(0);
    }

    /* Feedback section styling */
    #modalInfo textarea {
      transition: border-color 0.2s;
    }

    /* Enhanced star rating styling */
    #rating-stars .star:hover {
      background: rgba(255, 255, 255, 0.1) !important;
      transform: scale(1.1);
    }

    #rating-stars .star:active {
      transform: scale(0.95);
    }

    #modalInfo textarea:focus {
      outline: none;
      border-color: #4caf50;
      box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
    }

    #modalInfo .star {
      transition: all 0.2s;
    }

    #modalInfo .star:hover {
      transform: scale(1.2);
    }

    /* Modal close button styling */
    #modalCloseBtn:hover {
      background: rgba(255, 255, 255, 0.2) !important;
      transform: scale(1.1);
    }

    #modalCloseBtn:active {
      transform: scale(0.95);
    }

    /* Image loading optimization */
    #modalImg {
      transition: opacity 0.3s ease;
    }

    #modalImg.loading {
      opacity: 0.5;
      filter: blur(2px);
    }

    #modalImg.loaded {
      opacity: 1;
      filter: none;
    }

    /* Loading spinner for modal images */
    .image-loading-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 40px;
      height: 40px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid #fff;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      z-index: 10000;
    }

    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* Responsive design for smaller screens */
    @media (max-width: 768px) {
      #modal > div { flex-direction: column; gap: 15px; padding: 15px; }
      #modalInfo { flex: none; order: 2; width: 100%; }
      #modal img { max-height: 60vh; order: 1; }
      #modalInfo button { width: 100%; justify-content: center; }
      #modalInfo textarea { font-size: 16px; } /* Prevent zoom on iOS */
    }

    @media (max-width: 480px) {
      #modal > div { padding: 10px; }
      #modalInfo { padding: 15px; }
      #modal img { max-height: 50vh; }
      #modalInfo button { padding: 12px 16px; font-size: 16px; }
      #modalInfo .star { font-size: 20px; }
    }

    /* Prevent zoom on iOS for star rating */
    @media screen and (max-width: 768px) {
      #modalInfo .star {
        font-size: 24px !important;
        min-width: 32px;
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        touch-action: manipulation;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      /* Prevent zoom on all interactive elements in modal */
      #modalInfo button,
      #modalInfo textarea,
      #modalInfo input,
      #modalInfo .star {
        font-size: 16px !important;
        -webkit-text-size-adjust: 100%;
        -webkit-appearance: none;
      }

      /* Ensure modal content doesn't trigger zoom */
      #modalInfo {
        -webkit-text-size-adjust: none;
        text-size-adjust: none;
      }
    }
    @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
    @keyframes zoomIn { from { transform: scale(.85); } to { transform: scale(1); } }

    .empty { text-align: center; opacity: .7; padding: 28px 0; }

    /* Album Management Styles */
    .modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .8);
             justify-content: center; align-items: center; z-index: 1000; }
    .modal-content { background: rgba(255, 255, 255, 0.95); color: #333; padding: 20px; border-radius: 12px;
                      max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
    body.dark .modal-content { background: rgba(42, 42, 42, 0.95); color: #eee; }
    .album-form { margin: 20px 0; }
    .album-form input, .album-form textarea { width: 100%; margin: 8px 0; padding: 8px; border-radius: 6px; border: 1px solid #ddd; background: rgba(255, 255, 255, 0.9); }
    body.dark .album-form input, body.dark .album-form textarea { background: rgba(51, 51, 51, 0.9); color: #eee; border-color: #555; }
    .album-item { display: flex; justify-content: space-between; align-items: center;
                  padding: 10px; border: 1px solid #ddd; margin: 5px 0; border-radius: 6px; background: rgba(255, 255, 255, 0.9); }
    body.dark .album-item { border-color: #555; background: rgba(42, 42, 42, 0.9); }
    .album-item button { margin-left: 10px; }
    .close-btn { background: #666; color: #fff; border: none; padding: 8px 16px;
                 border-radius: 6px; cursor: pointer; margin-top: 10px; }
    .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .modal-buttons button { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; }
    .modal-buttons button:first-child { background: var(--green); color: #fff; }
    .modal-buttons button:last-child { background: #666; color: #fff; }

    /* Move to Album Button */
    #moveToAlbumBtn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    #moveToAlbumBtn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Album tag */
    .album-tag {
      position: absolute; bottom: 8px; left: 8px; right: 8px;
      background: rgba(0, 0, 0, 0.7); color: #fff; padding: 4px 8px;
      border-radius: 4px; font-size: 12px; text-align: center;
      backdrop-filter: blur(4px);
    }

    /* Enhanced Camera Info Styles */
    .photo-filename {
      font-weight: bold;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .camera-info {
      margin-bottom: 15px;
    }

    .camera-name {
      font-size: 18px;
      font-weight: bold;
      color: #4caf50;
      margin-bottom: 5px;
    }

    .lens-info {
      font-size: 14px;
      color: #81c784;
      font-style: italic;
    }

    .photo-settings {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 15px;
    }

    .setting-group {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 12px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .setting-label {
      font-weight: 600;
      color: #ccc;
      font-size: 13px;
    }

    .setting-value {
      font-weight: bold;
      color: #fff;
      font-size: 14px;
    }

    /* Camera info overlay on photo cards */
    .camera-overlay {
      position: absolute;
      top: 8px;
      left: 8px;
      right: 8px;
      background: linear-gradient(135deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.7));
      color: #fff;
      padding: 8px 10px;
      border-radius: 8px;
      font-size: 12px;
      text-align: center;
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      z-index: 2;
    }

    .camera-overlay .camera-model {
      font-weight: bold;
      color: #4caf50;
      margin-bottom: 4px;
      font-size: 13px;
    }

    .camera-overlay .camera-settings {
      display: flex;
      justify-content: space-around;
      align-items: center;
      font-size: 11px;
      opacity: 0.95;
    }

    .camera-overlay .setting {
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 45px;
    }

    .camera-overlay .setting-label {
      font-size: 9px;
      color: #ccc;
      margin-bottom: 2px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .camera-overlay .setting-value {
      font-weight: bold;
      color: #fff;
      font-size: 12px;
    }

    /* Make sure camera overlay appears above album tag */
    .photo-card:hover .camera-overlay {
      opacity: 1;
      transform: translateY(0);
    }

    .camera-overlay {
      opacity: 0.95;
      transition: all 0.3s ease;
    }

    /* Admin Login Modal Styles */
    .login-modal {
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border-radius: 16px;
      padding: 32px;
      max-width: 480px;
      width: 100%;
      position: relative;
      overflow: hidden;
    }

    .login-modal::before {
      content: '';
      position: absolute;
      top: -80px;
      left: -80px;
      width: 160px;
      height: 160px;
      background: linear-gradient(90deg, #06b6d4, #3b82f6);
      border-radius: 50%;
      filter: blur(36px);
      opacity: 0.18;
      animation: floaty 8s ease-in-out infinite;
    }

    .login-modal::after {
      content: '';
      position: absolute;
      bottom: -100px;
      right: -100px;
      width: 200px;
      height: 200px;
      background: linear-gradient(90deg, #06b6d4, #10b981);
      border-radius: 50%;
      filter: blur(36px);
      opacity: 0.18;
      animation: floaty2 9s ease-in-out infinite;
    }

    .login-header {
      position: relative;
      z-index: 2;
      text-align: center;
      margin-bottom: 24px;
    }

    .login-modal .brand {
      display: flex;
      gap: 12px;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
    }

    .login-modal .logo {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      background: linear-gradient(135deg, #34d399, #06b6d4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: #04212a;
      font-size: 20px;
      box-shadow: 0 6px 18px rgba(16,185,129,0.12);
    }

    .login-modal h1 {
      margin: 0;
      font-size: 20px;
      letter-spacing: -0.2px;
      color: #e6eef8;
    }

    .login-modal .tiny {
      font-size: 12px;
      color: rgba(230,238,248,0.6);
      margin-top: 4px;
    }

    .login-modal .lead {
      margin: 16px 0;
      color: rgba(230,238,248,0.85);
      opacity: 0.95;
      font-size: 14px;
      line-height: 1.5;
      text-align: center;
    }

    .login-modal label {
      font-size: 13px;
      color: rgba(230,238,248,0.85);
      margin-bottom: 6px;
      display: block;
    }

    .login-modal input[type="text"],
    .login-modal input[type="password"] {
      background: transparent;
      border: 1px solid rgba(255,255,255,0.06);
      padding: 12px 14px;
      border-radius: 10px;
      color: #e6eef8;
      outline: none;
      font-size: 15px;
      transition: box-shadow 0.18s, transform 0.12s;
      width: 100%;
      box-sizing: border-box;
    }

    .login-modal input:focus {
      box-shadow: 0 6px 18px rgba(16,185,129,0.09);
      transform: translateY(-2px);
      border-color: rgba(16,185,129,0.4);
    }

    .login-modal .row {
      display: flex;
      gap: 10px;
      align-items: center;
      justify-content: space-between;
      margin-top: 6px;
    }

    .login-modal .primary {
      background: linear-gradient(90deg, var(--accent), #06b6d4);
      color: #042a24;
      font-weight: 700;
      padding: 12px 16px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-size: 15px;
      transition: transform 0.12s, box-shadow 0.12s;
    }

    .login-modal .primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(16,185,129,0.12);
    }

    .login-modal .muted {
      color: rgba(230,238,248,0.65);
      font-size: 13px;
    }

    .login-modal .error-box {
      background: linear-gradient(90deg, rgba(220,38,38,0.08), rgba(255,255,255,0.01));
      border: 1px solid rgba(220,38,38,0.13);
      color: #ffdede;
      padding: 10px 12px;
      border-radius: 10px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: shake 0.6s;
      margin-bottom: 16px;
    }

    @keyframes shake {
      10% { transform: translateX(-6px); }
      30% { transform: translateX(6px); }
      50% { transform: translateX(-4px); }
      70% { transform: translateX(4px); }
      100% { transform: translateX(0); }
    }

    /* Shake animation for login form */
    .shake {
      animation: shake 0.6s;
    }

    /* Responsive adjustments for login modal */
    @media (max-width: 768px) {
      .login-modal {
        padding: 24px;
        margin: 16px;
      }

      .login-modal .row {
        flex-direction: column;
        gap: 12px;
      }

      .login-modal .primary {
        width: 100%;
      }
    }

    /* Admin Context Menu Styles */
    .admin-context-menu {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      user-select: none;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
    }

    .admin-context-menu .context-menu-item {
      transition: background-color 0.2s ease;
      white-space: nowrap;
    }

    .admin-context-menu .context-menu-item:hover {
      background: rgba(255, 255, 255, 0.1) !important;
    }

    .admin-context-menu .context-menu-item:active {
      background: rgba(255, 255, 255, 0.2) !important;
    }

    /* Mobile-specific context menu adjustments */
    @media (max-width: 768px) {
      .admin-context-menu {
        min-width: 180px;
        font-size: 16px; /* Prevent zoom on iOS */
        border-radius: 12px;
      }

      .admin-context-menu .context-menu-item {
        padding: 12px 16px;
        font-size: 16px;
      }
    }

    @media (max-width: 480px) {
      .admin-context-menu {
        min-width: 200px;
        font-size: 16px;
      }

      .admin-context-menu .context-menu-item {
        padding: 14px 18px;
        font-size: 16px;
      }
    }

    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
      .admin-context-menu .context-menu-item {
        min-height: 44px; /* iOS touch target minimum */
        display: flex;
        align-items: center;
      }

      .admin-context-menu .context-menu-item:active {
        background: rgba(255, 255, 255, 0.3) !important;
        transform: scale(0.98);
      }
    }

    /* Prevent text selection on context menu */
    .admin-context-menu,
    .admin-context-menu * {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-touch-callout: none;
    }

    /* Allow context menu in admin mode */
    body.admin {
      -webkit-touch-callout: default !important;
    }

    body.admin * {
      -webkit-touch-callout: default !important;
    }


    /* Animation for context menu appearance */
    @keyframes contextMenuFadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .admin-context-menu {
      animation: contextMenuFadeIn 0.15s ease-out;
    }
  </style>
  <script>const CSRF_TOKEN = "<?php echo addslashes(htmlspecialchars(csrf_token(), ENT_QUOTES)); ?>";</script>
</head>
<body class="dark <?php echo $isAdmin ? 'admin' : ''; ?>">
  <header>
    <div class="header-main">
      <h1>📸 Khanhs Photos Gallery</h1>
      <div class="album-navigation">
        <div class="album-indicator">
          <span class="album-icon">📁</span>
          <span class="album-text">Browsing:</span>
        </div>
        <select id="albumSelect" class="album-select">
          <option value="all">📸 All Photos</option>
        </select>
        <div class="album-stats" id="albumStats">
          <span class="stats-icon">🖼️</span>
          <span class="stats-count" id="photoCount">0</span>
          <span class="stats-text">photos</span>
        </div>
      </div>
    </div>

    <div class="header-controls">
      <div class="search-sort">
        <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm ảnh...">
        <select id="sortSelect">
          <option value="date-desc">🕒 Mới nhất</option>
          <option value="date-asc">📅 Cũ nhất</option>
          <option value="size-desc">📏 Lớn nhất</option>
          <option value="size-asc">📐 Nhỏ nhất</option>
        </select>
      </div>

      <div class="admin-actions">
        <?php if ($isAdmin): ?>
          <button class="btn primary" id="uploadBtn">⬆️ Upload</button>
          <button class="btn secondary" id="folderBtn">📂 Thư mục</button>
          <button class="btn secondary" id="albumBtn">📁 Quản lý</button>
          <button class="btn secondary" id="moveToAlbumBtn">📦 Chuyển</button>
          <button class="btn danger" onclick="deleteSelected(getSelectedFiles())">🗑️ Xóa</button>
          <button class="btn toggle" id="rightClickToggle" onclick="toggleRightClick()">
            <span id="toggleIcon">🚫</span>
            <span id="toggleText">Right-Click: OFF</span>
          </button>
          <a href="/admin_logout" class="btn logout">🚪 Thoát</a>
        <?php else: ?>
            <button onclick="showLoginModal()" class="btn login">🔒 Đăng nhập</button>
          <?php endif; ?>
      </div>

      <input type="file" id="fileInput" accept="image/*" multiple hidden>
      <input type="file" id="folderInput" webkitdirectory directory multiple hidden>
    </div>
  </header>

  <div id="progressWrapper"><div id="progressBar"></div></div>
  <div id="uploadStatus"></div>
  <div id="storageInfo">💾 Đang tải...</div>
  <div id="spinner"><div class="loader"></div></div>
  <main id="gallery"></main>
  <div id="modal">
    <div style="display: flex; align-items: flex-start; gap: 20px; max-width: 100%; max-height: 100%; padding: 20px; position: relative;">
      <!-- Close Button -->
      <button id="modalCloseBtn" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10001; transition: all 0.2s;">
        ✕
      </button>

      <div id="modalInfo" style="flex: 0 0 300px; background: rgba(0,0,0,0.7); padding: 20px; border-radius: 8px; color: white;"></div>
      <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
        <img id="modalImg" src="" alt="Photo" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
      </div>
    </div>
  </div>

  <!-- Album Management Modal -->
  <div id="albumModal" class="modal">
    <div class="modal-content">
      <h3>Quản lý Album</h3>
      <div id="albumList"></div>
      <div class="album-form">
        <input type="text" id="albumName" placeholder="Tên album">
        <textarea id="albumDesc" placeholder="Mô tả album"></textarea>
        <button id="createAlbumBtn">Tạo Album</button>
      </div>
      <button class="close-btn" onclick="closeAlbumModal()">Đóng</button>
    </div>
  </div>

  <!-- Upload Album Selection Modal -->
  <div id="uploadAlbumModal" class="modal">
    <div class="modal-content">
      <h3>Chọn Album để Upload</h3>
      <select id="uploadAlbumSelect">
        <option value="">Chọn album...</option>
      </select>
      <div class="modal-buttons">
        <button onclick="proceedUpload()">Upload</button>
        <button onclick="cancelUpload()">Hủy</button>
      </div>
    </div>
  </div>

  <!-- Move to Album Modal -->
  <div id="moveAlbumModal" class="modal">
    <div class="modal-content">
      <h3>Chuyển Ảnh vào Album</h3>
      <p id="moveAlbumCount">Đã chọn 0 ảnh</p>
      <select id="moveAlbumSelect">
        <option value="">Chọn album đích...</option>
      </select>
      <div class="modal-buttons">
        <button onclick="confirmMoveToAlbum()">Chuyển</button>
        <button onclick="cancelMoveToAlbum()">Hủy</button>
      </div>
    </div>
  </div>


  <!-- Admin Login Modal -->
  <div id="adminLoginModal" class="modal">
    <div class="modal-content login-modal">
      <div class="login-header">
        <a href="/" class="back-btn" style="display:block; margin-bottom:16px; color:#10b981; text-decoration:none; font-size:14px; font-weight:500;">
          ← Back to Gallery
        </a>
        <div class="brand">
          <div class="logo">PG</div>
          <div>
            <h1>Photo Gallery Admin</h1>
            <div class="tiny">Quản lý ảnh — upload / xóa / xem log</div>
          </div>
        </div>
        <p class="lead">Đăng nhập bằng tài khoản Admin để bật chế độ quản trị: upload ảnh, xóa ảnh và xem thông tin. Sai mật khẩu 3 lần sẽ chuyển về trang chính.</p>
      </div>

      <div id="loginError" class="error-box" style="display: none;"></div>

      <form id="adminLoginForm" style="display:flex; flex-direction:column; gap:12px;">
        <div>
          <label for="loginUsername">Tên đăng nhập</label>
          <input id="loginUsername" name="username" autocomplete="username" type="text" placeholder="admin" required>
        </div>
        <div>
          <label for="loginPassword">Mật khẩu</label>
          <input id="loginPassword" name="password" autocomplete="current-password" type="password" placeholder="••••••••" required>
        </div>
        <div class="row">
           <div class="muted tiny">Lần thử còn lại: <strong id="attemptsLeft">3</strong></div>
           <button id="loginSubmitBtn" class="primary" type="submit">Đăng nhập</button>
        </div>
        <div class="row" style="margin-top:6px;">
          <div class="tiny">Phiên bản: <strong>1.0</strong></div>
          <div class="tiny">Time: <span id="currentTime"><?php echo date('Y-m-d H:i'); ?></span></div>
        </div>
      </form>
    </div>
  </div>

  <script src="secret-page.js?v=<?php echo time(); ?>"></script>
  <script>
  // Global variables
  let currentRating = 0;
  let photos = [];
  let albums = [];
  let selectedAlbumForUpload = null;
  let pendingFiles = [];
  let selectedPhotosForMove = [];
  let currentPhotoIndex = -1;

  // === Download Photo Function ===
  window.downloadPhoto = function(filename) {
    if (!document.body.classList.contains('admin')) {
      alert('Access denied: Admin privileges required');
      return;
    }

    // Create a temporary link to download the file
    const link = document.createElement('a');
    link.href = 'uploads/' + encodeURIComponent(filename);
    link.download = filename;
    link.style.display = 'none';

    // Add to DOM, click, and remove
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // === Feedback System Functions ===
  window.initRatingStars = function() {
    const stars = document.querySelectorAll('#rating-stars .star');
    if (!stars.length) return;

    stars.forEach((star, index) => {
      star.addEventListener('click', () => {
        currentRating = index + 1;
        updateStarDisplay();
      });

      star.addEventListener('mouseover', () => {
        updateStarDisplay(index + 1);
      });

      star.addEventListener('mouseout', () => {
        updateStarDisplay(currentRating);
      });
    });

    // Initialize with current rating
    updateStarDisplay(currentRating);
  };

  function updateStarDisplay(rating = currentRating) {
    const stars = document.querySelectorAll('#rating-stars .star');
    stars.forEach((star, index) => {
      if (index < rating) {
        star.textContent = '⭐';
        star.style.color = '#ffd700';
      } else {
        star.textContent = '☆';
        star.style.color = '#ddd';
      }
    });
  }

  window.submitFeedback = function(filename) {
    if (currentRating === 0) {
      alert('Please select a rating (1-5 stars)');
      return;
    }

    const comment = document.getElementById('feedback-comment').value.trim();
    const submitBtn = document.getElementById('submit-feedback-btn');

    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Submitting...';

    // Prepare data
    const data = {
      photo: filename,
      rating: currentRating,
      comment: comment
    };

    // Send feedback
    fetch('feedback.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        alert('Thank you for your feedback! 🎉');
        // Clear form
        currentRating = 0;
        updateStarDisplay();
        document.getElementById('feedback-comment').value = '';
        // Reload feedback stats
        loadFeedbackStats(filename);
      } else {
        alert('Error: ' + (result.message || 'Failed to submit feedback'));
      }
    })
    .catch(error => {
      console.error('Feedback submission error:', error);
      alert('Network error. Please try again.');
    })
    .finally(() => {
      // Re-enable button
      submitBtn.disabled = false;
      submitBtn.textContent = '📝 Submit Feedback';
    });
  };

  window.deleteMyFeedback = function(filename) {
    if (!confirm('Are you sure you want to delete your feedback for this photo?')) {
      return;
    }

    const deleteBtn = document.getElementById('delete-feedback-btn');

    // Disable button and show loading
    deleteBtn.disabled = true;
    deleteBtn.textContent = '⏳ Deleting...';

    // Send delete request
    fetch('feedback.php', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        photo: filename
      })
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        alert('Your feedback has been deleted! 🗑️');
        // Clear form
        currentRating = 0;
        updateStarDisplay();
        document.getElementById('feedback-comment').value = '';
        // Reload feedback stats
        loadFeedbackStats(filename);
      } else {
        alert('Error: ' + (result.message || 'Failed to delete feedback'));
      }
    })
    .catch(error => {
      console.error('Feedback deletion error:', error);
      alert('Network error. Please try again.');
    })
    .finally(() => {
      // Re-enable button
      deleteBtn.disabled = false;
      deleteBtn.textContent = '🗑️ Delete My Feedback';
    });
  };

  window.deleteFeedbackById = function(filename, feedbackId) {
    if (!document.body.classList.contains('admin')) {
      alert('Access denied: Admin privileges required');
      return;
    }

    if (!confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
      return;
    }

    // Send admin delete request
    fetch('feedback.php', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        photo: filename,
        feedback_id: feedbackId
      })
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        alert('Feedback deleted successfully! 🗑️');
        // Reload feedback stats to update the display
        loadFeedbackStats(filename);
      } else {
        alert('Error: ' + (result.message || 'Failed to delete feedback'));
      }
    })
    .catch(error => {
      console.error('Admin feedback deletion error:', error);
      alert('Network error. Please try again.');
    });
  };

  function loadFeedbackStats(filename) {
    const statsDiv = document.getElementById('feedback-stats');
    if (!statsDiv) return;

    fetch(`feedback.php?photo=${encodeURIComponent(filename)}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        statsDiv.innerHTML = '<div style="color: #ff6b6b; font-size: 12px;">Unable to load feedback stats</div>';
        return;
      }

      const stats = data.stats || {};
      const comments = data.comments || [];

      let html = '';

      if (stats.total_ratings > 0) {
        const avgRating = (typeof stats.average_rating === 'number' && !isNaN(stats.average_rating))
          ? stats.average_rating.toFixed(1)
          : '0.0';
        html += `<div style="margin-bottom: 15px;">
          <div style="font-size: 14px; font-weight: bold; color: #4caf50; margin-bottom: 8px;">
            📊 Average: ${avgRating}/5.0 (${stats.total_ratings} reviews)
          </div>
          <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 10px;">
            <div style="text-align: center; font-size: 11px;">5⭐</div>
            <div style="text-align: center; font-size: 11px;">4⭐</div>
            <div style="text-align: center; font-size: 11px;">3⭐</div>
            <div style="text-align: center; font-size: 11px;">2⭐</div>
            <div style="text-align: center; font-size: 11px;">1⭐</div>
          </div>
          <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px;">
            <div style="background: #4caf50; height: 8px; border-radius: 4px;" title="${stats.five_stars} votes"></div>
            <div style="background: #8bc34a; height: 8px; border-radius: 4px;" title="${stats.four_stars} votes"></div>
            <div style="background: #ffc107; height: 8px; border-radius: 4px;" title="${stats.three_stars} votes"></div>
            <div style="background: #ff9800; height: 8px; border-radius: 4px;" title="${stats.two_stars} votes"></div>
            <div style="background: #f44336; height: 8px; border-radius: 4px;" title="${stats.one_star} votes"></div>
          </div>
        </div>`;
      } else {
        html += '<div style="color: #666; font-size: 12px; font-style: italic;">No reviews yet. Be the first to rate this photo!</div>';
      }

      if (comments && comments.length > 0) {
        html += '<div style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">';
        html += '<div style="font-size: 12px; font-weight: bold; margin-bottom: 8px; color: #81c784;">💬 Recent Comments:</div>';

        comments.slice(0, 3).forEach(comment => {
           const date = new Date(comment.created_at).toLocaleDateString();
           const isAdmin = document.body.classList.contains('admin');
           const adminDeleteBtn = isAdmin ? `<button onclick="deleteFeedbackById('${filename}', ${comment.id})" style="background: rgba(220,53,69,0.8); color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 10px; cursor: pointer; margin-left: 8px;" title="Delete this feedback (Admin)">🗑️</button>` : '';

           html += `<div style="margin-bottom: 8px; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; font-size: 11px;">
             <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
               <span style="color: #ffd700;">${'⭐'.repeat(comment.rating)}${'☆'.repeat(5-comment.rating)}</span>
               <div style="display: flex; align-items: center; gap: 4px;">
                 <span style="color: #ccc;">${date}</span>
                 ${adminDeleteBtn}
               </div>
             </div>
             ${comment.comment ? `<div style="color: #eee;">${comment.comment}</div>` : ''}
           </div>`;
         });

        html += '</div>';
      }

      statsDiv.innerHTML = html;

      // Check if user has already submitted feedback for this photo
      checkUserFeedback(filename);
    })
    .catch(error => {
      console.error('Error loading feedback stats:', error);
      statsDiv.innerHTML = '<div style="color: #ff6b6b; font-size: 12px;">Error loading feedback stats</div>';
    });
  }

  function checkUserFeedback(filename) {
    fetch(`feedback.php?photo=${encodeURIComponent(filename)}&check_user=true`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      const deleteBtn = document.getElementById('delete-feedback-btn');
      const submitBtn = document.getElementById('submit-feedback-btn');

      if (data.has_feedback) {
        // User has already submitted feedback - show delete button, hide submit
        if (deleteBtn) deleteBtn.style.display = 'inline-block';
        if (submitBtn) submitBtn.style.display = 'none';
      } else {
        // User hasn't submitted feedback - show submit button, hide delete
        if (deleteBtn) deleteBtn.style.display = 'none';
        if (submitBtn) submitBtn.style.display = 'inline-block';
      }
    })
    .catch(error => {
      console.error('Error checking user feedback:', error);
    });
  }

  // Admin Login Modal Functions - Make them globally accessible
  function showLoginModal() {
    const modal = document.getElementById('adminLoginModal');
    if (!modal) return;

    const usernameInput = document.getElementById('loginUsername');
    const passwordInput = document.getElementById('loginPassword');
    const errorDiv = document.getElementById('loginError');

    // Reset form
    if (usernameInput) usernameInput.value = '';
    if (passwordInput) passwordInput.value = '';
    if (errorDiv) errorDiv.style.display = 'none';

    const attemptsLeft = document.getElementById('attemptsLeft');
    if (attemptsLeft) attemptsLeft.textContent = '3';

    // Show modal
    modal.style.display = 'flex';

    // Focus on username input
    if (usernameInput) {
      setTimeout(() => usernameInput.focus(), 100);
    }
  }

  function hideLoginModal() {
    const modal = document.getElementById('adminLoginModal');
    if (modal) modal.style.display = 'none';
  }

  console.log('Script loaded, waiting for DOMContentLoaded');
  document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById("fileInput");
    const folderInput = document.getElementById("folderInput");
    const uploadBtn = document.getElementById("uploadBtn");
    const folderBtn = document.getElementById("folderBtn");
    const gallery = document.getElementById("gallery");
    const progressWrapper = document.getElementById("progressWrapper");
    const progressBar = document.getElementById("progressBar");
    const uploadStatus = document.getElementById("uploadStatus");
    const spinner = document.getElementById("spinner");
    const storageInfo = document.getElementById("storageInfo");
    const modal = document.getElementById("modal");
    const modalImg = document.getElementById("modalImg");
    const modalInfo = document.getElementById("modalInfo");
    const searchInput = document.getElementById("searchInput");
    const sortSelect = document.getElementById("sortSelect");
    const albumSelect = document.getElementById("albumSelect");
    const albumBtn = document.getElementById("albumBtn");
    const albumModal = document.getElementById("albumModal");
    const uploadAlbumModal = document.getElementById("uploadAlbumModal");
    const uploadAlbumSelect = document.getElementById("uploadAlbumSelect");
    const moveAlbumModal = document.getElementById("moveAlbumModal");
    const moveAlbumSelect = document.getElementById("moveAlbumSelect");
    const moveAlbumCount = document.getElementById("moveAlbumCount");
    const moveToAlbumBtn = document.getElementById("moveToAlbumBtn");

      // === Upload ảnh ===
      if (uploadBtn) {
        uploadBtn.addEventListener("click", () => fileInput.click());
        fileInput.addEventListener("change", e => handleFiles([...e.target.files]));
      }
      if (folderBtn) {
        folderBtn.addEventListener("click", () => folderInput.click());
        folderInput.addEventListener("change", e => {
          const files = [...e.target.files].filter(f => f.type.startsWith("image/"));
          handleFiles(files);
        });
      }

      function handleFiles(files) {
        if (!files.length) return;
        pendingFiles = files;
        loadAlbumsForUpload();
        uploadAlbumModal.style.display = "flex";
      }

      function proceedUpload() {
        if (!selectedAlbumForUpload) {
          alert("Vui lòng chọn album!");
          return;
        }

        uploadAlbumModal.style.display = "none";
        let uploaded = 0;
        uploadStatus.style.display = "block";
        progressWrapper.style.display = "block";

        (async () => {
          for (const f of pendingFiles) {
            uploadStatus.textContent = `Đang upload: ${f.name}`;
            // Handle "All Photos" selection - pass null for album_id
            const albumId = selectedAlbumForUpload === "all" ? null : selectedAlbumForUpload;
            await uploadOne(f, albumId);
            uploaded++;
            progressBar.style.width = (uploaded / pendingFiles.length * 100) + "%";
          }
          uploadStatus.style.display = "none";
          progressWrapper.style.display = "none";
          progressBar.style.width = "0%";
          loadGallery();
          fileInput.value = "";
          folderInput.value = "";
          pendingFiles = [];
          selectedAlbumForUpload = null;
        })();
      }

      function cancelUpload() {
        uploadAlbumModal.style.display = "none";
        pendingFiles = [];
        selectedAlbumForUpload = null;
        fileInput.value = "";
        folderInput.value = "";
      }

      function loadAlbumsForMove() {
        fetch("albums.php", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(response => {
          if (response.error) {
            albums = response.albums || [];
          } else {
            albums = response;
          }
          moveAlbumSelect.innerHTML = '<option value="">Chọn album đích...</option>';

          // Filter out "All Photos" virtual album
          const realAlbums = albums.filter(album => album.id !== 'all');

          realAlbums.forEach(album => {
            const option = document.createElement("option");
            option.value = album.id;
            option.textContent = album.name;
            moveAlbumSelect.appendChild(option);
          });
        })
        .catch(error => {
          console.error('Error loading albums for move:', error);
          albums = [];
          moveAlbumSelect.innerHTML = '<option value="">Chọn album đích...</option>';
        });
      }


      function updatePhotoCount(count) {
        const photoCountElement = document.getElementById("photoCount");
        if (photoCountElement) {
          photoCountElement.textContent = count;
        }
      }

      function uploadOne(file, albumId = null) {
        return new Promise(resolve => {
          const formData = new FormData();
          formData.append("file", file);
          formData.append("csrf", CSRF_TOKEN);
          if (albumId) {
            formData.append("album_id", albumId);
          }
          const xhr = new XMLHttpRequest();
          xhr.open("POST", "upload.php", true);
          xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
              // Tiến trình từng file
            }
          };
          xhr.onload = () => resolve();
          xhr.onerror = () => {
            alert(`Upload thất bại: ${file.name}`);
            resolve();
          };
          xhr.send(formData);
        });
      }

      // === Storage info ===
      function loadStorage() {
        fetch("storage.php", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(r => r.text())
          .then(txt => storageInfo.innerHTML = "💾 " + txt)
          .catch(() => storageInfo.textContent = "💾 Error");
      }

      // === Gallery ===
      function renderPhotos(photosData) {
        photos = photosData;
        gallery.innerHTML = "";

        // Update photo count
        updatePhotoCount(photos.length);

        if (!photos.length) {
          gallery.innerHTML = '<p class="empty">Chưa có ảnh nào</p>';
          return;
        }
        photos.forEach((p, index) => {
          const card = document.createElement("div");
          card.className = "photo-card";
          card.dataset.index = index;

          <?php if ($isAdmin): ?>
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.className = "select-photo";
          checkbox.value = p.filename;
          card.appendChild(checkbox);
          <?php endif; ?>

          const img = document.createElement("img");
          img.loading = "lazy";
          img.src = p.thumb;
          img.alt = p.filename;
          img.dataset.full = "uploads/" + encodeURIComponent(p.filename);
          card.appendChild(img);

          // Camera info overlay - Always show for better visibility
          const cameraInfo = getCameraInfo(p.metadata);
          const cameraOverlay = document.createElement("div");
          cameraOverlay.className = "camera-overlay";

          let settingsHtml = '';
          if (cameraInfo.aperture || cameraInfo.focal || cameraInfo.iso) {
            settingsHtml = `
              <div class="camera-settings">
                ${cameraInfo.aperture ? `<div class="setting"><div class="setting-label">f-stop</div><div class="setting-value">${cameraInfo.aperture}</div></div>` : ''}
                ${cameraInfo.focal ? `<div class="setting"><div class="setting-label">focal</div><div class="setting-value">${cameraInfo.focal}</div></div>` : ''}
                ${cameraInfo.iso ? `<div class="setting"><div class="setting-label">ISO</div><div class="setting-value">${cameraInfo.iso}</div></div>` : ''}
              </div>
            `;
          }

          cameraOverlay.innerHTML = `
            <div class="camera-model">${cameraInfo.model}</div>
            ${settingsHtml}
          `;

          card.appendChild(cameraOverlay);

          // Album indicator
          if (p.album_name && p.album_name !== 'General') {
            const albumTag = document.createElement("div");
            albumTag.className = "album-tag";
            albumTag.textContent = p.album_name;
            card.appendChild(albumTag);
          }

          <?php if ($isAdmin): ?>
          const del = document.createElement("button");
          del.className = "delete-btn";
          del.textContent = "🗑️";
          del.onclick = () => deleteSelected([p.filename]);
          card.appendChild(del);
          <?php endif; ?>

          gallery.appendChild(card);
        });
      }

      function loadGallery(skipAlbumReload = false) {
        spinner.style.display = "block";
        const albumFilter = albumSelect.value || 'all';
        const sortValue = sortSelect.value || 'date-desc';
        const [sortBy, sortOrder] = sortValue.split('-');
        const url = `list.php?album=${encodeURIComponent(albumFilter)}&sort=${encodeURIComponent(sortBy)}&order=${encodeURIComponent(sortOrder)}`;

        fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(photos => {
          renderPhotos(photos);
          spinner.style.display = "none";
          loadStorage();
          if (!skipAlbumReload) {
            loadAlbums();
          }
        })
        .catch(() => {
          spinner.style.display = "none";
          gallery.innerHTML = "<p class='empty'>Lỗi load gallery</p>";
        });
      }

      function getSelectedFiles() {
        const checkboxes = document.querySelectorAll(".select-photo:checked");
        const files = [...checkboxes].map(cb => cb.value);
        return files;
      }

      function deleteSelected(files) {
        if (!files.length) { alert("Chưa chọn ảnh nào!"); return; }
        if (!confirm("Xóa " + files.length + " ảnh?")) return;

        const body = "csrf=" + encodeURIComponent(CSRF_TOKEN) +
                     "&" + files.map(f => "files[]=" + encodeURIComponent(f)).join("&");

        fetch("delete.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            'X-Requested-With': 'XMLHttpRequest'
          },
          body
        }).then(r => r.json()).then(resp => {
          if (resp.success) loadGallery();
          else alert("Xóa thất bại: " + (resp.error || "Unknown error"));
        }).catch(error => {
          console.error('Delete error:', error);
          alert("Lỗi kết nối khi xóa!");
        });
      }

function updateModalInfo(index) {
  const photo = photos[index];

  // Show loading state
  modalImg.classList.add('loading');
  modalImg.classList.remove('loaded');

  // Add loading spinner
  const existingSpinner = modal.querySelector('.image-loading-spinner');
  if (existingSpinner) {
    existingSpinner.remove();
  }

  const spinner = document.createElement('div');
  spinner.className = 'image-loading-spinner';
  modalImg.parentElement.appendChild(spinner);

  // Load image with promise
  const imgPromise = new Promise((resolve, reject) => {
    modalImg.onload = () => {
      modalImg.classList.remove('loading');
      modalImg.classList.add('loaded');
      spinner.remove();
      resolve();
    };
    modalImg.onerror = () => {
      spinner.remove();
      reject();
    };
    modalImg.src = "uploads/" + encodeURIComponent(photo.filename);
  });

  // Reset rating and initialize stars
  currentRating = 0;
  setTimeout(() => {
    initRatingStars();
    loadFeedbackStats(photo.filename);
  }, 100);

  // Build info
  let info = document.body.classList.contains("admin")
    ? `<div class="photo-filename" style="font-size: 16px; font-weight: bold; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.3);">📁 ${photo.filename}</div>`
    : "";

  // Add download button for admin users
  if (document.body.classList.contains("admin")) {
    info += `<div style="margin-bottom: 20px;">
      <button onclick="downloadPhoto('${photo.filename}')" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
        <span>⬇️</span> Download Full Size
      </button>
    </div>`;
  }

  // Add feedback/rating section for all users
  info += `<div style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
    <div style="font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #4caf50;">💬 Rate this photo</div>
    <div id="rating-stars" style="display: flex; gap: 12px; margin-bottom: 10px; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 8px; justify-content: center;">
      <span class="star" data-rating="1" style="font-size: 32px; cursor: pointer; color: #ddd; padding: 8px; border-radius: 6px; transition: all 0.2s; min-width: 48px; min-height: 48px; display: flex; align-items: center; justify-content: center;">☆</span>
      <span class="star" data-rating="2" style="font-size: 32px; cursor: pointer; color: #ddd; padding: 8px; border-radius: 6px; transition: all 0.2s; min-width: 48px; min-height: 48px; display: flex; align-items: center; justify-content: center;">☆</span>
      <span class="star" data-rating="3" style="font-size: 32px; cursor: pointer; color: #ddd; padding: 8px; border-radius: 6px; transition: all 0.2s; min-width: 48px; min-height: 48px; display: flex; align-items: center; justify-content: center;">☆</span>
      <span class="star" data-rating="4" style="font-size: 32px; cursor: pointer; color: #ddd; padding: 8px; border-radius: 6px; transition: all 0.2s; min-width: 48px; min-height: 48px; display: flex; align-items: center; justify-content: center;">☆</span>
      <span class="star" data-rating="5" style="font-size: 32px; cursor: pointer; color: #ddd; padding: 8px; border-radius: 6px; transition: all 0.2s; min-width: 48px; min-height: 48px; display: flex; align-items: center; justify-content: center;">☆</span>
    </div>
    <textarea id="feedback-comment" placeholder="Share your thoughts about this photo..." style="width: 100%; min-height: 60px; padding: 8px; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; background: rgba(255,255,255,0.1); color: white; resize: vertical; font-family: inherit; font-size: 13px;" maxlength="500"></textarea>
    <div style="display: flex; gap: 8px; margin-top: 8px;">
      <button onclick="submitFeedback('${photo.filename}')" id="submit-feedback-btn" style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
        <span>📝</span> Submit Feedback
      </button>
      <button onclick="deleteMyFeedback('${photo.filename}')" id="delete-feedback-btn" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: none;">
        <span>🗑️</span> Delete My Feedback
      </button>
    </div>
    <div id="feedback-stats" style="margin-top: 15px; font-size: 12px; color: rgba(255,255,255,0.8);"></div>
  </div>`;

  // Read metadata from saved JSON
  const md = photo.metadata || {};
  const cameraInfo = getCameraInfo(md);
  const lens = md.EXIF?.UndefinedTag?.[0] || ""; // Lens info if available
  const dateTaken = md.EXIF?.DateTimeOriginal || "Unknown";
  // Try multiple possible field names for shutter speed
  const shutter = (md.EXIF?.ExposureTime || md.EXIF?.ShutterSpeedValue || md.EXIF?.ShutterSpeed) ?
    formatShutterSpeed(md.EXIF.ExposureTime || md.EXIF.ShutterSpeedValue || md.EXIF.ShutterSpeed) : "Unknown";
  const exposureMode = md.EXIF?.ExposureMode || "";
  const meteringMode = md.EXIF?.MeteringMode || "";

  // Debug EXIF data
  console.log('EXIF Debug for:', photo.filename);
  console.log('Full metadata:', md);
  console.log('EXIF data:', md.EXIF);
  console.log('Camera Model:', md.EXIF?.Model, md.IFD0?.Model);
  console.log('Shutter Speed fields:', {
    ExposureTime: md.EXIF?.ExposureTime,
    ShutterSpeedValue: md.EXIF?.ShutterSpeedValue,
    ShutterSpeed: md.EXIF?.ShutterSpeed
  });
  console.log('Device Make:', md.EXIF?.Make, md.IFD0?.Make);
  console.log('Date Taken:', md.EXIF?.DateTimeOriginal, md.EXIF?.DateTime);

  info += `
    <div class="camera-info" style="margin-bottom: 20px;">
      <div class="camera-name" style="font-size: 18px; font-weight: bold; color: #4caf50; margin-bottom: 8px;">📷 ${cameraInfo.model}</div>
      ${lens ? `<div class="lens-info" style="font-size: 14px; color: #81c784; font-style: italic;">🔍 ${lens}</div>` : ''}
    </div>
    <div class="photo-settings" style="display: grid; gap: 12px;">
      ${cameraInfo.aperture ? `<div class="setting-group" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span class="setting-label" style="font-weight: 600; color: #ccc;">Aperture:</span><span class="setting-value" style="font-weight: bold; color: white;">${cameraInfo.aperture}</span></div>` : ''}
      ${shutter !== "Unknown" ? `<div class="setting-group" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span class="setting-label" style="font-weight: 600; color: #ccc;">Shutter:</span><span class="setting-value" style="font-weight: bold; color: white;">${shutter}</span></div>` : ''}
      ${cameraInfo.iso ? `<div class="setting-group" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span class="setting-label" style="font-weight: 600; color: #ccc;">ISO:</span><span class="setting-value" style="font-weight: bold; color: white;">${cameraInfo.iso}</span></div>` : ''}
      ${cameraInfo.focal ? `<div class="setting-group" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span class="setting-label" style="font-weight: 600; color: #ccc;">Focal:</span><span class="setting-value" style="font-weight: bold; color: white;">${cameraInfo.focal}</span></div>` : ''}
      ${dateTaken !== "Unknown" ? `<div class="setting-group" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.1); border-radius: 6px;"><span class="setting-label" style="font-weight: 600; color: #ccc;">Taken:</span><span class="setting-value" style="font-weight: bold; color: white;">${formatDate(dateTaken)}</span></div>` : ''}
    </div>
  `;

  modalInfo.innerHTML = info;

  return imgPromise;
}

function formatShutterSpeed(exposureTime) {
  if (!exposureTime) return "Unknown";

  console.log('Shutter speed input:', exposureTime, typeof exposureTime);

  let time = exposureTime;

  // Handle different formats of exposure time
  if (typeof exposureTime === 'string') {
    // Handle rational format like "1/100" or "0.01"
    if (exposureTime.includes('/')) {
      const parts = exposureTime.split('/');
      if (parts.length === 2) {
        const numerator = parseFloat(parts[0]);
        const denominator = parseFloat(parts[1]);
        if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
          time = numerator / denominator;
        }
      }
    } else {
      time = parseFloat(exposureTime);
    }
  } else if (Array.isArray(exposureTime)) {
    // Handle EXIF rational arrays like [1, 100]
    if (exposureTime.length === 2) {
      const numerator = exposureTime[0];
      const denominator = exposureTime[1];
      if (denominator !== 0) {
        time = numerator / denominator;
      }
    }
  }

  time = parseFloat(time);

  console.log('Parsed shutter speed:', time);

  if (isNaN(time) || time <= 0) return "Unknown";

  // Handle very long exposures (slower than 1 second)
  if (time >= 1) {
    // Use standard photography format with quotes for slow speeds
    const rounded = Math.round(time * 10) / 10;
    return `${rounded}"`;
  }

  // Handle fast exposures (faster than 1 second)
  if (time > 0) {
    // Calculate the denominator for 1/X format
    const denominator = Math.round(1 / time);

    // Use standard photography format without "s" for fast speeds
    if (denominator === 1) return "1";
    return `1/${denominator}`;
  }

  return "Unknown";
}

function formatDate(dateString) {
  try {
    // Handle EXIF date format: "2023:12:25 14:30:45"
    const date = new Date(dateString.replace(/:/g, '-'));
    if (isNaN(date.getTime())) {
      return dateString; // Return original if parsing fails
    }
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
  } catch {
    return dateString;
  }
}

function getCameraInfo(metadata) {
  if (!metadata) {
    return {
      hasInfo: true,
      model: 'Unknown Camera',
      aperture: null,
      focal: null,
      iso: null
    };
  }

  const exif = metadata.EXIF || {};
  const ifd0 = metadata.IFD0 || {};

  // Try multiple possible field names for camera model
  const make = exif.Make || ifd0.Make || '';
  const model = exif.Model || ifd0.Model || 'Unknown Camera';
  const cameraModel = make && make !== model ? `${make} ${model}` : model;

  console.log('Camera detection:', { make, model, cameraModel });

  // Format aperture/f-stop correctly from APEX value
  let aperture = null;

  // Try FNumber first (most common)
  if (exif.FNumber) {
    let fNumber = exif.FNumber;

    // Handle different formats of FNumber
    if (typeof fNumber === 'string') {
      // Handle rational format like "18/10" or "F1.8"
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
        // Handle format like "F1.8"
        fNumber = parseFloat(fNumber.substring(1));
      } else {
        fNumber = parseFloat(fNumber);
      }
    } else if (Array.isArray(fNumber)) {
      // Handle EXIF rational arrays like [18, 10]
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
      // Format with appropriate decimal places
      let formatted;
      if (fNumber >= 10) {
        // For apertures like f/11, f/16, etc.
        formatted = Math.round(fNumber);
      } else if (fNumber % 1 === 0) {
        // For whole numbers like f/2, f/4, f/8
        formatted = fNumber;
      } else {
        // For decimal apertures like f/1.8, f/2.8, f/4.0
        formatted = Math.round(fNumber * 10) / 10;
      }
      aperture = `f/${formatted}`;
    }
  }

  // Fallback to ApertureValue if FNumber not available
  if (!aperture && exif.ApertureValue) {
    let apex = exif.ApertureValue;

    // Handle different formats of ApertureValue
    if (typeof apex === 'string') {
      apex = parseFloat(apex);
    } else if (Array.isArray(apex)) {
      // Handle EXIF rational arrays
      if (apex.length === 2) {
        const numerator = apex[0];
        const denominator = apex[1];
        if (denominator !== 0) {
          apex = numerator / denominator;
        }
      }
    }

    apex = parseFloat(apex);

    if (!isNaN(apex)) {
      const fNumber = Math.pow(2, apex / 2);
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

  console.log('Aperture detection:', {
    FNumber: exif.FNumber,
    FNumberType: typeof exif.FNumber,
    ApertureValue: exif.ApertureValue,
    ApertureValueType: typeof exif.ApertureValue,
    result: aperture
  });

  // Format focal length correctly
  let focal = null;
  if (exif.FocalLength) {
    // Handle both string and number formats
    let focalLength = typeof exif.FocalLength === 'string' ? parseFloat(exif.FocalLength) : exif.FocalLength;

    // Handle cases where focal length is stored as rational (e.g., 280/10 = 28)
    // or multiplied by 10, 100, etc.
    if (focalLength > 1000) {
      // If focal length is unreasonably high, it might be multiplied by 10, 100, etc.
      if (focalLength % 10 === 0 && focalLength / 10 <= 1000) {
        focalLength = focalLength / 10;
      } else if (focalLength % 100 === 0 && focalLength / 100 <= 1000) {
        focalLength = focalLength / 100;
      } else if (focalLength % 1000 === 0 && focalLength / 1000 <= 1000) {
        focalLength = focalLength / 1000;
      }
    }

    // Handle rational numbers (e.g., 280 might represent 28.0)
    if (focalLength >= 100 && focalLength % 10 === 0) {
      // Check if it's a multiple of 10 that should be divided
      const divided = focalLength / 10;
      if (divided >= 10 && divided <= 1000) {
        focalLength = divided;
      }
    }

    // Ensure we have a valid number
    if (!isNaN(focalLength) && focalLength > 0 && focalLength <= 2000) {
      // Round to nearest mm for common focal lengths
      const rounded = Math.round(focalLength);
      focal = `${rounded}mm`;
    }
  }

  console.log('Focal length detection:', { FocalLength: exif.FocalLength, result: focal });

  // Format ISO nicely - try multiple field names
  const iso = exif.ISOSpeedRatings || exif.ISO || exif.ISOSpeed || null;

  console.log('ISO detection:', { ISOSpeedRatings: exif.ISOSpeedRatings, ISO: exif.ISO, ISOSpeed: exif.ISOSpeed, result: iso });

  return {
    hasInfo: true,
    model: cameraModel,
    aperture,
    focal,
    iso
  };
}

// Image preloading for faster modal loading
function preloadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(src);
    img.onerror = reject;
    img.src = src;
  });
}

function preloadAdjacentImages(currentIndex) {
  if (!photos || !photos.length) return;

  const preloadPromises = [];

  // Preload next image
  if (currentIndex < photos.length - 1) {
    const nextSrc = "uploads/" + encodeURIComponent(photos[currentIndex + 1].filename);
    preloadPromises.push(preloadImage(nextSrc));
  }

  // Preload previous image
  if (currentIndex > 0) {
    const prevSrc = "uploads/" + encodeURIComponent(photos[currentIndex - 1].filename);
    preloadPromises.push(preloadImage(prevSrc));
  }

  // Preload next 2 images for better performance
  if (currentIndex < photos.length - 2) {
    const nextNextSrc = "uploads/" + encodeURIComponent(photos[currentIndex + 2].filename);
    preloadPromises.push(preloadImage(nextNextSrc));
  }

  return Promise.all(preloadPromises);
}

// Click on image
gallery.addEventListener("click", (e) => {
  const card = e.target.closest(".photo-card");
  if (!card) return;

  // Prevent modal opening if clicking on checkbox, delete button, or other interactive elements
  if (e.target.tagName.toLowerCase() === "input" ||
      e.target.tagName.toLowerCase() === "button" ||
      e.target.closest('.delete-btn') ||
      e.target.closest('.select-photo')) {
    return;
  }

  e.preventDefault();
  currentPhotoIndex = parseInt(card.dataset.index, 10);
  modal.style.display = "flex";

  // Start preloading adjacent images immediately
  preloadAdjacentImages(currentPhotoIndex);

  updateModalInfo(currentPhotoIndex);
});

// Global keyboard event listener for F12 and other keys
document.addEventListener("keydown", (e) => {
  if (e.key === "F12") {
    // Don't prevent default - allow dev tools to open
    return;
  }

  if (modal.style.display !== "flex") return;

  if (e.key === "ArrowLeft" && currentPhotoIndex > 0) {
    e.preventDefault();
    currentPhotoIndex--;
    // Preload images for the new position
    preloadAdjacentImages(currentPhotoIndex);
    updateModalInfo(currentPhotoIndex);
  } else if (e.key === "ArrowRight" && currentPhotoIndex < photos.length - 1) {
    e.preventDefault();
    currentPhotoIndex++;
    // Preload images for the new position
    preloadAdjacentImages(currentPhotoIndex);
    updateModalInfo(currentPhotoIndex);
  } else if (e.key === "Escape") {
    e.preventDefault();
    modal.style.display = "none";
    modalImg.src = "";
    currentPhotoIndex = -1;
  }
});


// Close modal on click outside image
modal.addEventListener("click", (e) => {
  // Only close if clicking on the modal background, not on the image or info
  if (e.target === modal) {
    modal.style.display = "none";
    modalImg.src = "";
    currentPhotoIndex = -1;
  }
});

// Close modal with X button
const modalCloseBtn = document.getElementById("modalCloseBtn");
if (modalCloseBtn) {
  modalCloseBtn.addEventListener("click", () => {
    modal.style.display = "none";
    modalImg.src = "";
    currentPhotoIndex = -1;
  });
}


      // === Album Management ===
      if (albumBtn) {
        // Only show album management for admins
        if (document.body.classList.contains("admin")) {
          albumBtn.style.display = "inline-block";
          albumBtn.addEventListener("click", () => {
            loadAlbums();
            albumModal.style.display = "flex";
          });
        } else {
          albumBtn.style.display = "none";
        }
      }

      function loadAlbums() {
        fetch("albums.php", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(r => r.json())
          .then(response => {
            if (response.error) {
              // User is not admin, use default albums
              albums = response.albums || [];
            } else {
              // User is admin, use full album data
              albums = response;
            }
            // Add "All Photos" as the first option
            albums.unshift({
              id: 'all',
              name: '📸 All Photos',
              description: 'View all photos from all albums'
            });
            updateAlbumSelects();
            renderAlbumList();
          })
          .catch(error => {
            console.error('Error loading albums:', error);
            albums = [{
              id: 'all',
              name: '📸 All Photos',
              description: 'View all photos from all albums'
            }];
            updateAlbumSelects();
            renderAlbumList();
          });
      }

      function loadAlbumsForUpload() {
        fetch("albums.php", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(r => r.json())
          .then(response => {
            if (response.error) {
              albums = response.albums || [];
            } else {
              albums = response;
            }
            uploadAlbumSelect.innerHTML = '<option value="">Chọn album...</option>';

            // Add "All Photos" option first
            const allPhotosOption = document.createElement("option");
            allPhotosOption.value = "all";
            allPhotosOption.textContent = "📸 All Photos";
            uploadAlbumSelect.appendChild(allPhotosOption);

            // Filter out "All Photos" virtual album from the rest
            const realAlbums = albums.filter(album => album.id !== 'all');

            realAlbums.forEach(album => {
              const option = document.createElement("option");
              option.value = album.id;
              option.textContent = album.name;
              uploadAlbumSelect.appendChild(option);
            });
          })
          .catch(error => {
            console.error('Error loading albums for upload:', error);
            albums = [];
            uploadAlbumSelect.innerHTML = '<option value="">Chọn album...</option>';
          });
      }

      function updateAlbumSelects() {
        // Remember the currently selected album before clearing
        const currentSelection = albumSelect.value;

        albumSelect.innerHTML = '';

        if (!albums || !Array.isArray(albums)) {
          return;
        }

        albums.forEach(album => {
          const option = document.createElement("option");
          option.value = album.id;
          option.textContent = album.name || 'Unknown';

          // Preserve the current selection, or default to "All Photos" if none selected
          if (album.id === currentSelection) {
            option.selected = true;
          } else if (!currentSelection && (album.id === 'all' || album.id === '')) {
            option.selected = true;
          }

          albumSelect.appendChild(option);
        });
      }

      function renderAlbumList() {
        const albumList = document.getElementById("albumList");
        albumList.innerHTML = "";

        if (!albums || !Array.isArray(albums)) {
          albumList.innerHTML = "<p>Không thể tải danh sách album</p>";
          return;
        }

        // Filter out the "All Photos" virtual album from management
        const realAlbums = albums.filter(album => album.id !== 'all');

        if (realAlbums.length === 0) {
          albumList.innerHTML = "<p>Chưa có album nào</p>";
          return;
        }

        realAlbums.forEach(album => {
          const div = document.createElement("div");
          div.className = "album-item";
          div.innerHTML = `
            <div>
              <strong>${album.name || 'Unknown'}</strong>
              <br><small>${album.description || 'Không có mô tả'}</small>
            </div>
            <div>
              <button onclick="editAlbum(${album.id})">Sửa</button>
              <button onclick="deleteAlbum(${album.id})" style="background: #dc3545;">Xóa</button>
            </div>
          `;
          albumList.appendChild(div);
        });
      }

      function createAlbum() {
        const name = document.getElementById("albumName").value.trim();
        const desc = document.getElementById("albumDesc").value.trim();

        if (!name) {
          alert("Vui lòng nhập tên album!");
          return;
        }

        fetch("albums.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ name, description: desc, csrf: CSRF_TOKEN })
        })
        .then(r => r.json())
        .then(resp => {
          if (resp.success) {
            loadAlbums();
            document.getElementById("albumName").value = "";
            document.getElementById("albumDesc").value = "";
          } else {
            alert("Lỗi tạo album: " + resp.error);
          }
        });
      }

      function deleteAlbum(id) {
        if (!confirm("Xóa album này? Tất cả ảnh sẽ được chuyển về album chung.")) return;

        fetch(`albums.php?id=${id}&csrf=${encodeURIComponent(CSRF_TOKEN)}`, {
          method: "DELETE",
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(r => r.json())
          .then(resp => {
            if (resp.success) {
              loadAlbums();
            } else {
              alert("Lỗi xóa album: " + resp.error);
            }
          });
      }

      function editAlbum(id) {
        const album = albums.find(a => a.id == id);
        if (!album) return;

        const newName = prompt("Tên album mới:", album.name);
        if (!newName) return;

        const newDesc = prompt("Mô tả mới:", album.description || "");

        fetch("albums.php", {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ id, name: newName, description: newDesc, csrf: CSRF_TOKEN })
        })
        .then(r => r.json())
        .then(resp => {
          if (resp.success) {
            loadAlbums();
          } else {
            alert("Lỗi cập nhật album: " + resp.error);
          }
        });
      }

      // Make functions globally accessible for onclick handlers
      window.closeAlbumModal = function() {
        albumModal.style.display = "none";
      };

      window.cancelUpload = function() {
        uploadAlbumModal.style.display = "none";
        pendingFiles = [];
        selectedAlbumForUpload = null;
        fileInput.value = "";
        folderInput.value = "";
      };

      window.proceedUpload = function() {
        if (!selectedAlbumForUpload) {
          alert("Vui lòng chọn album!");
          return;
        }

        uploadAlbumModal.style.display = "none";
        let uploaded = 0;
        uploadStatus.style.display = "block";
        progressWrapper.style.display = "block";

        (async () => {
          for (const f of pendingFiles) {
            uploadStatus.textContent = `Đang upload: ${f.name}`;
            await uploadOne(f, selectedAlbumForUpload);
            uploaded++;
            progressBar.style.width = (uploaded / pendingFiles.length * 100) + "%";
          }
          uploadStatus.style.display = "none";
          progressWrapper.style.display = "none";
          progressBar.style.width = "0%";
          loadGallery();
          fileInput.value = "";
          folderInput.value = "";
          pendingFiles = [];
          selectedAlbumForUpload = null;
        })();
      };

      window.confirmMoveToAlbum = function() {
        const targetAlbumId = moveAlbumSelect.value;
        if (!targetAlbumId) {
          alert("Vui lòng chọn album đích!");
          return;
        }

        if (selectedPhotosForMove.length === 0) {
          alert("Không có ảnh nào được chọn!");
          return;
        }

        // Send request to move photos
        fetch("move_photos.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            photo_filenames: selectedPhotosForMove,
            album_id: targetAlbumId,
            csrf: CSRF_TOKEN
          })
        })
        .then(r => r.json())
        .then(response => {
          if (response.success) {
            alert(`Đã chuyển ${selectedPhotosForMove.length} ảnh vào album!`);
            moveAlbumModal.style.display = "none";
            // Clear selections
            document.querySelectorAll('.select-photo:checked').forEach(cb => cb.checked = false);
            selectedPhotosForMove = [];
            // Reload gallery
            loadGallery();
          } else {
            alert("Lỗi khi chuyển ảnh: " + (response.error || "Unknown error"));
          }
        })
        .catch(error => {
          console.error('Error moving photos:', error);
          alert("Lỗi kết nối khi chuyển ảnh!");
        });
      };

      window.cancelMoveToAlbum = function() {
        moveAlbumModal.style.display = "none";
        selectedPhotosForMove = [];
      };

      function closeAlbumModal() {
        albumModal.style.display = "none";
      }

      // Upload album selection
      uploadAlbumSelect.addEventListener("change", (e) => {
        selectedAlbumForUpload = e.target.value;
      });

      // Move album selection
      moveAlbumSelect.addEventListener("change", (e) => {
        // Just for consistency, no special handling needed
      });

      // Create album button
      const createAlbumBtn = document.getElementById("createAlbumBtn");
      if (createAlbumBtn) {
        createAlbumBtn.addEventListener("click", function() {
          const name = document.getElementById("albumName").value.trim();
          const desc = document.getElementById("albumDesc").value.trim();

          if (!name) {
            alert("Vui lòng nhập tên album!");
            return;
          }

          fetch("albums.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ name, description: desc, csrf: CSRF_TOKEN })
          })
          .then(r => r.json())
          .then(resp => {
            if (resp.success) {
              loadAlbums();
              document.getElementById("albumName").value = "";
              document.getElementById("albumDesc").value = "";
            } else {
              alert("Lỗi tạo album: " + resp.error);
            }
          })
          .catch(error => {
            console.error('Error creating album:', error);
            alert("Lỗi kết nối khi tạo album!");
          });
        });
      }

      // Move to Album functionality
      if (moveToAlbumBtn) {
        moveToAlbumBtn.addEventListener("click", () => {
          selectedPhotosForMove = getSelectedFiles();
          if (selectedPhotosForMove.length === 0) {
            alert("Vui lòng chọn ít nhất một ảnh!");
            return;
          }
          moveAlbumCount.textContent = `Đã chọn ${selectedPhotosForMove.length} ảnh`;
          loadAlbumsForMove();
          moveAlbumModal.style.display = "flex";
        });
      }

      // Tìm kiếm
      searchInput.addEventListener("input", () => {
        const query = searchInput.value.toLowerCase();

        if (!query.trim()) {
          // If search is cleared, reload gallery to show all photos in current album
          // Preserve current album selection
          const currentAlbum = albumSelect.value;
          loadGallery(true); // Skip album reload to prevent timing issues
          // Ensure album selection is maintained after reload
          setTimeout(() => {
            if (currentAlbum && albumSelect.querySelector(`option[value="${currentAlbum}"]`)) {
              albumSelect.value = currentAlbum;
            }
          }, 100);
          return;
        }

        // Filter current photos by search query
        const filteredPhotos = photos.filter(p => {
          return (p.filename && p.filename.toLowerCase().includes(query)) ||
                 (document.body.classList.contains("admin") && p.uploader && p.uploader.toLowerCase().includes(query));
        });
        renderPhotos(filteredPhotos);
        updatePhotoCount(filteredPhotos.length);
      });

      // Lọc theo album
      albumSelect.addEventListener("change", () => {
        const albumFilter = albumSelect.value;

        // Reload gallery when changing album selection to show photos from selected album
        loadGallery();
      });

      // Sắp xếp
      sortSelect.addEventListener("change", () => {
        // Reload gallery with current album and new sort settings
        loadGallery();
      });

      // Kiểm tra hoạt động để auto logout
      function checkActivity() {
        if (document.body.classList.contains("admin")) {
          fetch("check_activity.php", {
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(r => r.text())
          .then(resp => {
            if (resp === "logout") window.location.href = "/admin_logout";
          });
        }
      }
      setInterval(checkActivity, 60000);


      // Handle login form submission
      document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('loginUsername').value;
        const password = document.getElementById('loginPassword').value;
        const submitBtn = document.getElementById('loginSubmitBtn');
        const errorDiv = document.getElementById('loginError');

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang xử lý...';

        // Hide previous error
        errorDiv.style.display = 'none';

        // Prepare form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('csrf', CSRF_TOKEN);

        // Send login request
        fetch('index.php?admin_login_ajax=1', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Login successful - reload page to show admin interface
            window.location.reload();
          } else {
            // Login failed
            errorDiv.innerHTML = `
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 17h.01" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10.29 3h3.42l7 12.12A2 2 0 0 1 19.7 19H4.3a2 2 0 0 1-1.01-3.88L10.29 3z" stroke="#ffb4b4" stroke-width="0" fill="rgba(220,38,38,0.14)"/>
              </svg>
              <div>${data.error}</div>
            `;
            errorDiv.style.display = 'flex';

            // Update attempts left
            if (data.attempts_left !== undefined) {
              document.getElementById('attemptsLeft').textContent = data.attempts_left;
            }

            // Add shake animation
            const form = document.getElementById('adminLoginForm');
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 700);

            // Vibrate if supported
            if (navigator.vibrate) {
              navigator.vibrate(100);
            }
          }
        })
        .catch(error => {
          console.error('Login error:', error);
          errorDiv.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 17h.01" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="#ffb4b4" stroke-width="2"/>
            </svg>
            <div>Lỗi kết nối. Vui lòng thử lại.</div>
          `;
          errorDiv.style.display = 'flex';
        })
        .finally(() => {
          // Re-enable submit button
          submitBtn.disabled = false;
          submitBtn.textContent = 'Đăng nhập';
        });
      });

      // Handle Enter key in password field
      document.getElementById('loginPassword').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          document.getElementById('adminLoginForm').dispatchEvent(new Event('submit'));
        }
      });

      // Close modal when clicking outside
      document.getElementById('adminLoginModal').addEventListener('click', function(e) {
        if (e.target === this) {
          hideLoginModal();
        }
      });

      window.updateToggleButton = function() {
        const toggleBtn = document.getElementById('rightClickToggle');
        const toggleIcon = document.getElementById('toggleIcon');
        const toggleText = document.getElementById('toggleText');

        if (rightClickEnabled) {
          toggleBtn.classList.add('active');
          toggleIcon.textContent = '✅';
          toggleText.textContent = 'Right-Click: ON';
        } else {
          toggleBtn.classList.remove('active');
          toggleIcon.textContent = '🚫';
          toggleText.textContent = 'Right-Click: OFF';
        }
      };

      window.toggleRightClick = function() {
        rightClickEnabled = !rightClickEnabled;
        localStorage.setItem('rightClickEnabled', rightClickEnabled);
        updateToggleButton();

        // Show feedback
        const status = rightClickEnabled ? 'enabled' : 'disabled';
        console.log(`🔧 Right-click for users has been ${status}`);
        alert(`Right-click for users has been ${status}!`);
      };

      // Initialize toggle button if admin
      if (document.body.classList.contains('admin')) {
        updateToggleButton();
      }

      // Close modal on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('adminLoginModal').style.display === 'flex') {
          hideLoginModal();
        }
      });


      loadGallery();
    });

  </script>
</body>
</html>

