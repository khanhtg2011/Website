<?php
session_start(); // Khởi tạo session
require __DIR__ . "/config.php";
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Debug: Log session status
error_log("SESSION INIT: Session ID: " . session_id() . ", is_admin: " . ($isAdmin ? 'true' : 'false') . ", request: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Visitor tracking (only for non-admin users to avoid skewing analytics)
if (!$isAdmin) {
    $visitorsFile = __DIR__ . '/data/visitors.json';

    // Ensure data directory exists
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
        'page' => $_SERVER['REQUEST_URI'] ?? '/',
        'session_id' => session_id()
    ];

    // Check if this visitor was already recorded recently (within last 5 minutes)
    $recentVisit = false;
    foreach ($visitors as $existingVisitor) {
        if ($existingVisitor['ip'] === $visitorInfo['ip'] &&
            $existingVisitor['session_id'] === $visitorInfo['session_id'] &&
            (time() - strtotime($existingVisitor['timestamp'])) < 300) { // 5 minutes
            $recentVisit = true;
            break;
        }
    }

    // Add new visitor if not recently recorded
    if (!$recentVisit) {
        $visitors[] = $visitorInfo;

        // Clean up old entries (keep only last 1000 entries to prevent file from growing too large)
        if (count($visitors) > 1000) {
            $visitors = array_slice($visitors, -1000);
        }

        // Save visitor data
        file_put_contents($visitorsFile, json_encode($visitors, JSON_PRETTY_PRINT));
    }
}

// Xử lý logic dựa trên URI
$request = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';

// Handle AJAX login requests
if (isset($_GET['admin_login_ajax']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    error_log("LOGIN AJAX: Login attempt detected");

    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Check admin credentials from database first, fallback to hardcoded
    $login_success = false;

    // Try database first
    if ($db && DB_AVAILABLE) {
        try {
            $stmt = $db->prepare("SELECT password FROM admin_credentials WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();

            if ($admin && $admin['password'] === $pass) {
                $login_success = true;
            }
        } catch (Exception $e) {
            // Database query failed, fall back to hardcoded
            error_log("Database admin login failed: " . $e->getMessage());
        }
    }

    // Fallback to hardcoded credentials if database not available or query failed
    if (!$login_success && $user === "Khanh" && $pass === "0799102011") {
        $login_success = true;
    }

    if ($login_success) {
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

// Handle private gallery logout
if (isset($_GET['logout_private'])) {
    unset($_SESSION['private_gallery_access']);
    unset($_SESSION['private_gallery_access_time']);
    header("Location: /");
    exit;
}

// Handle regular admin login page (fallback)
if ($request === 'admin_login' && !isset($_SESSION['is_admin'])) {
  // Admin login logic
  if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
  $error = "";
  if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $user = trim($_POST['username'] ?? '');
      $pass = $_POST['password'] ?? '';

      // Check admin credentials from database first, fallback to hardcoded
      $login_success = false;
  
      // Try database first
      if ($db && DB_AVAILABLE) {
          try {
              $stmt = $db->prepare("SELECT password FROM admin_credentials WHERE username = ?");
              $stmt->bind_param("s", $user);
              $stmt->execute();
              $result = $stmt->get_result();
              $admin = $result->fetch_assoc();
              $stmt->close();
  
              if ($admin && $admin['password'] === $pass) {
                  $login_success = true;
              }
          } catch (Exception $e) {
              // Database query failed, fall back to hardcoded
              error_log("Database admin login failed: " . $e->getMessage());
          }
      }
  
      // Fallback to hardcoded credentials if database not available or query failed
      if (!$login_success && $user === "Khanh" && $pass === "0799102011") {
          $login_success = true;
      }

      if ($login_success) {
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
} elseif ($request === 'admin_logout') {
    // Debug: Log logout attempt
    error_log("LOGOUT: Starting logout process for request: " . $request);
    error_log("LOGOUT: Current session ID: " . session_id());
    error_log("LOGOUT: is_admin before logout: " . (isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 'not set'));

    // Start fresh session to ensure we can modify it
    session_start();

    // Clear all session variables
    $_SESSION = array();

    // Destroy the session completely
    $session_name = session_name();
    $session_id = session_id();

    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Clear all possible session cookies
    if (isset($_COOKIE[$session_name])) {
        setcookie($session_name, '', time() - 42000, '/');
        setcookie($session_name, '', time() - 42000, '', $_SERVER['HTTP_HOST'] ?? '');
        setcookie($session_name, '', time() - 42000, '/', $_SERVER['HTTP_HOST'] ?? '');
    }

    // Clear PHPSESSID cookie specifically
    if (isset($_COOKIE['PHPSESSID'])) {
        setcookie('PHPSESSID', '', time() - 42000, '/');
    }

    error_log("LOGOUT: Session destroyed, redirecting to home page");

    // Force redirect with no-cache headers
    header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: /?logged_out=1&t=" . time());
    exit;
} elseif ($request === 'list' && $isAdmin) {
    header('Content-Type: application/json');
    include __DIR__ . '/list.php';
    exit;
}
?>
<script>
// Right-click is disabled for users, enabled for admin

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
      // For non-admin users, always disable right-click
      e.preventDefault();
      showAdminContextMenu(e, e.target);
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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="format-detection" content="telephone=no" />
  <link rel="icon" type="image/png" href="/favicon.png">
  <title>📸 Khanhs Photos Gallery</title>
  <!-- Performance hints for better loading -->
  <meta name="theme-color" content="#4caf50">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <!-- Performance optimizations - Third-party scripts removed for faster initial page load -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- DNS prefetch for faster image loading -->
  <link rel="dns-prefetch" href="//fonts.googleapis.com">
  <link rel="dns-prefetch" href="//fonts.gstatic.com">
  <!-- Removed: JSDelivr CDN (6 KiB, 1ms) and Google Tag Manager (2 KiB, 0ms) -->
  <!-- These can be added back if needed for analytics or EXIF processing -->
  <style>
    :root { --green: #4caf50; }
    * { box-sizing: border-box; }

    /* Screen reader only class for accessibility */
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
    html, body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #fafafa; color: #222;
                 transition: background .3s, color .3s; min-height: 100vh; width: 100%; height: 100%; }
    body.dark { background: #121212; color: #eee; }

    /* Ensure all main containers have transparent backgrounds */
    main, #gallery, .photo-card { background: transparent !important; }

    /* Remove white space at bottom */
    main { margin-bottom: 0; padding-bottom: 0; }
    body { margin-bottom: 0; padding-bottom: 0; }
    html { margin-bottom: 0; padding-bottom: 0; }



    /* Ensure no white space anywhere */
    * {
      box-sizing: border-box;
    }

    /* Remove any default margins that could cause white space */
    body, html {
      margin: 0 !important;
    }

    /* Main container will have its own margin rules */
    main {
      margin: 0;
    }


    /* Make sure gallery fills available space */
    #gallery {
      margin: 0 auto 40px !important;
      padding: 18px !important;
      gap: 30px !important;
    }

    /* Optimized loading styles for better Speed Index */
    .photo-card img {
      transition: opacity 0.2s ease;
    }

    .photo-card img[loading="lazy"] {
      opacity: 0;
    }

    .photo-card img[loading="eager"] {
      opacity: 1;
    }

    /* Critical above-the-fold content optimization */
    #gallery {
      contain: layout style paint;
    }

    .photo-card {
      contain: layout style paint;
      will-change: auto;
    }

    /* Force no white space anywhere */
    html, body {
      height: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      overflow-x: hidden !important;
    }

    main {
      min-height: 100vh !important;
      margin: 0 !important;
      padding: 0 !important;
    }


    /* Ensure gallery is visible and centered */
    #gallery {
      opacity: 1 !important;
      visibility: visible !important;
      display: grid !important;
    }

    /* Make sure photo cards are visible */
    .photo-card {
      opacity: 1 !important;
      visibility: visible !important;
      display: block !important;
    }

/* Make sure the layout extends to full width */
* { box-sizing: border-box; }

/* Main container - full width for gallery centering */
main {
  width: 100%;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  position: relative;
  display: block;
}



header { position: sticky; top: 0; z-index: 10;
         background: var(--green); color: #fff;
         box-shadow: 0 4px 20px rgba(0, 0, 0, .2);
         border-bottom: 3px solid rgba(255, 255, 255, 0.1);
         width: 100%; margin: 0; }

/* Ensure gallery is visible and centered */
#gallery {
  opacity: 1 !important;
  visibility: visible !important;
  display: grid !important;
  justify-content: center !important;
  gap: 30px !important;
  max-width: 1400px;
  margin: 0 auto !important;
}
    body.dark header { background: linear-gradient(135deg, #2a2a2a, #1a1a1a); }

    .header-main { display: flex; justify-content: space-between; align-items: center;
                   padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                   max-width: 1400px; margin: 0 auto; }

    /* Private Gallery Button */
    .private-gallery-btn {
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.2s;
      margin-left: 10px;
    }
    .private-gallery-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    /* Header toggle button */
    .header-toggle-btn {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: white;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s;
      margin-left: 10px;
    }
    .header-toggle-btn:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    .header-toggle-btn.hidden {
      background: linear-gradient(135deg, #f44336, #d32f2f);
    }
    .header-toggle-btn.hidden:hover {
      background: linear-gradient(135deg, #d32f2f, #b71c1c);
    }
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
                       align-items: center; flex-wrap: wrap; gap: 12px;
                       max-width: 1400px; margin: 0 auto; }

    .search-sort { display: none !important; }
    /* Search and sort removed */

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

    #progressWrapper { width: min(900px, 92%); margin: 14px auto 0;
                       background: #e9e9e9; border-radius: 10px;
                       display: none; height: 10px; overflow: hidden; }
    #progressBar { height: 100%; width: 0%;
                   background: linear-gradient(90deg, var(--green), #81c784);
                   transition: width .2s; }
    #uploadStatus { text-align: center; margin: 10px auto; display: none; }


    #spinner { display: none; text-align: center; padding: 28px; }
    .loader { border: 6px solid #f3f3f3; border-top: 6px solid var(--green);
              border-radius: 50%; width: 40px; height: 40px;
              animation: spin 1s linear infinite; margin: auto; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Gallery rule removed - consolidated above */
    .photo-card { position: relative; border-radius: 8px; overflow: hidden;
                  cursor: pointer; opacity: 0; transform: scale(.88);
                  animation: fadeIn .5s forwards; background: transparent;
                  width: 100%; max-width: 400px; height: 200px; /* Fixed dimensions to prevent CLS */
                  margin: 0 auto; /* Center within grid cell */ }
    .photo-card img { width: 100%; height: 100%; object-fit: cover;
                      display: block; }
    @keyframes fadeIn { to { opacity: 1; transform: scale(1); } }

    /* Responsive breakpoints */
    @media (min-width: 1200px) {
      #gallery { grid-template-columns: repeat(5, minmax(200px, 1fr)); }
      .photo-card { height: 200px; }
      .photo-card img { height: 200px; }
    }
    @media (min-width: 768px) and (max-width: 1199px) {
      #gallery { grid-template-columns: repeat(3, minmax(180px, 1fr)); }
      .photo-card { height: 180px; }
      .photo-card img { height: 180px; }
    }
    @media (max-width: 767px) {
      main { padding: 0 18px; }
      #gallery { grid-template-columns: 1fr; }
      .photo-card { height: 200px; max-width: 100%; }
      .photo-card img { height: 200px; }
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
        margin: 2px; /* Add small margin for better touch separation */
      }

      /* Improve admin action buttons layout on mobile */
      .admin-actions {
        flex-wrap: wrap;
        gap: 8px;
      }

      .admin-actions .btn {
        flex: 1;
        min-width: 120px;
        justify-content: center;
      }

      /* Select mode toggle button specific mobile styling */
      #selectModeToggle {
        min-width: 140px;
        font-size: 14px;
      }

      /* Search and sort removed */

      .album-select {
        padding: 12px 14px;
        min-height: 44px;
        font-size: 16px;
      }
    }
    @media (max-width: 480px) {
      #gallery { grid-template-columns: repeat(1, 1fr); }
      .photo-card { height: 200px; }
      .photo-card img { height: 200px; }

      /* Improve select mode on very small screens */
      .select-photo {
        transform: scale(2.2);
        top: 12px;
        left: 12px;
      }

      /* Stack admin buttons vertically on very small screens */
      .admin-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .admin-actions .btn {
        width: 100%;
        margin: 4px 0;
      }
    }

    /* Landscape orientation adjustments */
    @media (max-height: 500px) and (orientation: landscape) {
      .photo-card { height: 140px; }
      .photo-card img {
        height: 140px;
      }

      .header-main {
        padding: 8px 16px;
      }

      .header-controls {
        padding: 6px 16px;
      }

      .admin-actions .btn {
        padding: 8px 12px;
        font-size: 14px;
        min-height: 36px;
      }
    }

    .select-photo {
      position: absolute; top: 6px; left: 6px; z-index: 3;
      transform: scale(1.4); background: rgba(255, 255, 255, 0.8);
      padding: 2px; border-radius: 4px;
    }

    /* Mobile-friendly checkbox sizing */
    @media (max-width: 768px) {
      .select-photo {
        transform: scale(1.8);
        top: 8px;
        left: 8px;
      }
    }

    @media (max-width: 480px) {
      .select-photo {
        transform: scale(2);
        top: 10px;
        left: 10px;
      }
    }

    .delete-btn { position: absolute; top: 8px; right: 8px;
                  background: rgba(0, 0, 0, .6); color: #fff;
                  border: none; border-radius: 50%; padding: 6px 9px;
                  cursor: pointer; z-index: 2;
                  opacity: 0; transform: scale(.6); pointer-events: none;
                  transition: opacity .25s, transform .25s; }
    .delete-btn:hover { background: rgba(0, 0, 0, .8); }
    body.admin .delete-btn { opacity: 1; transform: scale(1); pointer-events: auto; }

    #modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .85);
      justify-content: center;
      align-items: center;
      padding: 18px;
      animation: fadeInModal .25s;
      z-index: 10000;
    }

    #modal > div {
      animation: modalSlideIn .3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
      }
      to {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }
    #modal > div { display: flex; align-items: flex-start; gap: 20px; max-width: 95vw; max-height: 95vh; padding: 20px; }
    #modal img {
      max-width: 70vw;
      max-height: 85vh;
      object-fit: contain;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.5);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    /* Photo transition animations */
    #modal img.fade-out {
      opacity: 0;
      transform: scale(0.95);
    }

    #modal img.fade-in {
      opacity: 1;
      transform: scale(1);
    }

    /* Directional slide animations for navigation */
    #modal img.slide-left {
      transform: translateX(-100%) scale(0.9);
      opacity: 0;
    }

    #modal img.slide-right {
      transform: translateX(100%) scale(0.9);
      opacity: 0;
    }

    #modal img.slide-center {
      transform: translateX(0) scale(1);
      opacity: 1;
    }
    #modalInfo {
      flex: 0 0 350px;
      background: rgba(0,0,0,0.8);
      padding: 25px;
      border-radius: 12px;
      color: white;
      transition: opacity 0.3s ease, transform 0.3s ease;
      backdrop-filter: blur(10px);
    }

    #modalInfo.fade-out {
      opacity: 0;
      transform: translateX(-20px);
    }

    #modalInfo.fade-in {
      opacity: 1;
      transform: translateX(0);
    }

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
      #modal > div { flex-direction: column; gap: 15px; padding: 15px; max-width: 98vw; max-height: 98vh; }
      #modalInfo { flex: none; order: 2; width: 100%; max-width: none; }
      #modal img { max-width: 95vw; max-height: 70vh; order: 1; }
      #modalInfo button { width: 100%; justify-content: center; }
      #modalInfo textarea { font-size: 16px; } /* Prevent zoom on iOS */
    }

    @media (max-width: 480px) {
      #modal > div { padding: 10px; max-width: 99vw; max-height: 99vh; }
      #modalInfo { padding: 15px; max-width: none; }
      #modal img { max-width: 98vw; max-height: 60vh; }
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

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .empty-state .empty-icon {
      font-size: 64px;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state h3 {
      margin: 0 0 10px 0;
      font-size: 24px;
      font-weight: 600;
      color: #333;
    }

    .empty-state p {
      margin: 0 0 30px 0;
      font-size: 16px;
      color: #666;
    }

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

    /* Hide camera overlay and album tags on mobile for cleaner view */
    @media (max-width: 768px) {
      .camera-overlay,
      .album-tag {
        display: none !important;
      }
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

    /* Drag-to-select styles */
    .drag-selection {
      position: absolute;
      border: 2px solid #4caf50;
      background: rgba(76, 175, 80, 0.1);
      pointer-events: none;
      z-index: 1000;
    }

    /* Drag-to-select styles */
    .selection-rectangle {
      position: absolute;
      border: 2px solid #4caf50;
      background: rgba(76, 175, 80, 0.2);
      pointer-events: none;
      z-index: 10;
    }

    /* Load More Button Styles */
    #loadMoreContainer {
      margin: 20px auto;
      max-width: 1400px;
      width: 100%;
      text-align: center;
      padding: 0 20px;
      box-sizing: border-box;
    }

    #loadMoreBtn {
      background: linear-gradient(135deg, #4caf50, #45a049) !important;
      border: none !important;
      color: white !important;
      font-weight: 600 !important;
      transition: all 0.3s ease !important;
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3) !important;
    }

    #loadMoreBtn:hover {
      background: linear-gradient(135deg, #45a049, #3d8b40) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4) !important;
    }

    #loadMoreBtn:disabled {
      background: #ccc !important;
      cursor: not-allowed !important;
      transform: none !important;
      box-shadow: none !important;
    }

    #loadingMore .loader {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #4caf50;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto;
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
<body class="dark <?php echo $isAdmin ? 'admin' : ''; ?>" onload="console.log('PAGE LOAD: Admin status on page load:', document.body.classList.contains('admin'), 'Session is_admin:', <?php echo $isAdmin ? 'true' : 'false'; ?>)">
  <header>
    <div class="header-main">
      <h1>📸 Khanhs Photos Gallery</h1>
      <div class="album-navigation">
        <div class="album-indicator">
          <span class="album-icon">📁</span>
          <span class="album-text">Browsing:</span>
        </div>
        <label for="albumSelect" class="sr-only">Select Album</label>
        <select id="albumSelect" class="album-select" aria-label="Select album to view">
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
      <div class="admin-actions">
        <?php if ($isAdmin): ?>
          <button class="btn primary" id="uploadBtn">⬆️ Upload</button>
          <button class="btn secondary" id="folderBtn">📂 Thư mục</button>
          <button class="btn secondary" id="albumBtn">📁 Quản lý</button>
          <a href="/admin-gallery.php" class="btn secondary" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">💝 Private Memories</a>
          <button class="btn secondary" id="deselectAllBtn" onclick="deselectAllPhotos()" style="display: none;">❌ Bỏ chọn</button>
          <button class="btn secondary" id="moveToAlbumBtn">📦 Chuyển</button>
          <button class="btn danger" onclick="deleteSelected(getSelectedFiles())">🗑️ Xóa</button>
          <button class="btn toggle" id="selectModeToggle" onclick="toggleSelectMode()">
            <span id="selectModeIcon">👆</span>
            <span id="selectModeText">Select Mode: OFF</span>
          </button>
          <button class="btn secondary" onclick="backupToWayback()">📦 Backup</button>
          <button class="btn secondary" onclick="checkForUpdates()">🔄 Check Updates</button>
          <a href="/admin_logout" class="btn logout" onclick="console.log('LOGOUT: Logout button clicked, current admin status:', document.body.classList.contains('admin'))">🚪 Thoát</a>
        <?php else: ?>
            <button onclick="showPrivateGalleryModal()" class="private-gallery-btn">💝 Private Gallery</button>
            <button onclick="showLoginModal()" class="btn login">🔒 Đăng nhập</button>
          <?php endif; ?>
      </div>

      <input type="file" id="fileInput" accept="image/*" multiple hidden>
      <input type="file" id="folderInput" webkitdirectory directory multiple hidden>
    </div>
  </header>

  <div id="progressWrapper"><div id="progressBar"></div></div>
  <div id="uploadStatus"></div>
  <div id="spinner"><div class="loader"></div></div>
  <main id="gallery"></main>
  <div id="loadMoreContainer" style="text-align: center; padding: 20px; display: none; border-top: 2px solid #eee; margin-top: 20px;">
    <button id="loadMoreBtn" class="btn primary" onclick="loadMorePhotos()" style="font-size: 16px; padding: 12px 24px; background: linear-gradient(135deg, #4caf50, #45a049);">
      📥 Load 35 More Photos
    </button>
    <div id="loadingMore" style="display: none; margin-top: 10px;">
      <div class="loader" style="width: 30px; height: 30px;"></div>
      <p style="margin: 10px 0 0 0; color: #666;">Loading 35 more photos...</p>
    </div>
  </div>
  <!-- Private Gallery Access Modal -->
  <div id="privateGalleryModal" class="modal" style="display: none;">
    <div class="modal-content" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 255, 255, 0.95)); max-width: 400px; padding: 30px; border-radius: 15px; text-align: center;">
      <h3 style="color: #dc3545; margin-bottom: 20px;">🔒 Private Gallery Access</h3>
      <p style="margin-bottom: 20px; color: #666;">Enter password to access private memories</p>

      <div id="privateGalleryError" style="display: none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>

      <form id="privateGalleryForm" onsubmit="return false;">
        <input type="password" id="privateGalleryPassword" placeholder="Enter password" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; font-size: 16px;" required>
        <div style="display: flex; gap: 10px;">
          <button type="submit" style="flex: 1; background: #dc3545; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer;">Access Gallery</button>
          <button type="button" onclick="closePrivateGalleryModal()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer;">Cancel</button>
        </div>
      </form>
    </div>
  </div>

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
        <form style="margin: 10px 0;" onsubmit="return false;">
          <label for="albumPassword" style="display: block; margin-bottom: 5px; font-size: 14px; color: #666;">Mật khẩu (tùy chọn - để tạo album riêng tư)</label>
          <input type="password" id="albumPassword" name="albumPassword" autocomplete="new-password" placeholder="Nhập mật khẩu để bảo vệ album" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </form>
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

  <!-- Album Password Modal -->
  <div id="albumPasswordModal" class="modal">
    <div class="modal-content login-modal">
      <div class="login-header">
        <div class="brand">
          <div class="logo">🔒</div>
          <div>
            <h1>Album Riêng Tư</h1>
            <div class="tiny">Nhập mật khẩu để truy cập album</div>
          </div>
        </div>
      </div>

      <div id="passwordError" class="error-box" style="display: none;"></div>

      <form id="albumPasswordForm" style="display:flex; flex-direction:column; gap:12px;" onsubmit="handleAlbumPasswordSubmit(event)">
        <input type="text" name="username" value="album_user" style="display:none;" autocomplete="username" aria-hidden="true">
        <div>
          <label for="albumPasswordInput">Mật khẩu album</label>
          <input id="albumPasswordInput" name="password" autocomplete="current-password" type="password" placeholder="••••••••" required>
        </div>
        <div style="display:none;">
          <label for="albumPasswordUsername">Username</label>
          <input type="text" id="albumPasswordUsername" name="username" value="album_user" autocomplete="username" aria-hidden="true">
        </div>
        <div class="row">
          <div class="muted tiny">Album: <strong id="albumPasswordName"></strong></div>
          <button id="albumPasswordSubmitBtn" class="primary" type="submit">Mở Album</button>
        </div>
      </form>
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

  <script src="secret-page.js?v=<?php echo time(); ?>" defer></script>

  <!-- Service Worker Registration for Performance -->
  <script>
    // Global variable to track service worker updates
    window.serviceWorkerUpdateAvailable = false;

    // Register service worker immediately for better performance
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
          console.log('Service Worker registered successfully:', registration.scope);

          // Handle updates - just mark as available, don't prompt automatically
          registration.addEventListener('updatefound', function() {
            const newWorker = registration.installing;
            newWorker.addEventListener('statechange', function() {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New version available - mark it but don't prompt
                window.serviceWorkerUpdateAvailable = true;
                console.log('Service Worker update available - use admin button to apply');
              }
            });
          });
        })
        .catch(function(error) {
          console.log('Service Worker registration failed:', error);
        });
    }

    // Function to check and apply service worker updates (admin only)
    window.checkForUpdates = function() {
      if (!document.body.classList.contains('admin')) {
        alert('Admin access required');
        return;
      }

      if (window.serviceWorkerUpdateAvailable) {
        if (confirm('A new version is available. Reload to update?')) {
          window.location.reload();
        }
      } else {
        alert('No updates available. Your app is up to date!');
      }
    };
  </script>
  <script>
  // Global variables
  let currentRating = 0;
  let photos = [];
  let albums = [];
  let selectedAlbumForUpload = null;
  let pendingFiles = [];
  let selectedPhotosForMove = [];
  let currentPhotoIndex = -1;
  let selectModeEnabled = localStorage.getItem('selectModeEnabled') === 'true'; // Default to false

  // Album password unlock tracking (temporary per session)
  window.temporarilyUnlockedAlbums = {};

  // Pagination variables
  let currentOffset = 0;
  let isLoadingMore = false;
  let hasMorePhotos = true;
  const photosPerLoad = 35; // Load 35 photos at a time


  // Swipe variables for modal
  let touchStartX = 0;
  let touchStartY = 0;
  let isSwiping = false;

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

  // === Backup to Wayback Machine Function ===
  window.backupToWayback = function() {
    if (!document.body.classList.contains('admin')) {
      alert('Admin access required for backup');
      return;
    }

    const waybackUrl = 'https://web.archive.org/save/khanh.cloud';

    fetch(waybackUrl, {
      method: 'GET',
      mode: 'no-cors' // Required for cross-origin requests to Wayback Machine
    })
    .then(() => {
      alert('Backup request sent to Wayback Machine! Archiving may take some time.');
    })
    .catch(error => {
      console.log('Wayback Machine request sent (CORS prevents response reading)');
      alert('Backup request sent to Wayback Machine! Check archive.org later to confirm.');
    });
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
      statsDiv.innerHTML = '<div style="color: #ff6b6b; font-size: 12px;">Error loading feedback stats</div>';
    });
  }

  // Load more photos function
  window.loadMorePhotos = function() {
    console.log('loadMorePhotos called: hasMorePhotos =', hasMorePhotos, 'isLoadingMore =', isLoadingMore);
    if (!hasMorePhotos || isLoadingMore) {
      console.log('Load more blocked: hasMorePhotos =', hasMorePhotos, 'isLoadingMore =', isLoadingMore);
      return;
    }

    console.log('📥 Loading 35 more photos...');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingMore = document.getElementById('loadingMore');

    // Show loading state
    loadMoreBtn.style.display = 'none';
    loadingMore.style.display = 'block';

    // Load more photos
    loadGallery(false, true);
  };

  // Update load more button visibility
  window.updateLoadMoreButton = function() {
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingMore = document.getElementById('loadingMore');

    console.log('updateLoadMoreButton called: hasMorePhotos =', hasMorePhotos, 'photos.length =', photos.length);

    if (hasMorePhotos && photos.length > 0) {
      loadMoreContainer.style.display = 'block';
      loadMoreBtn.style.display = 'inline-block';
      loadingMore.style.display = 'none';
      console.log('Load more button shown');

      // Preload next batch when approaching the end
      if (photos.length >= photosPerLoad && hasMorePhotos) {
        preloadNextBatch();
      }
    } else {
      loadMoreContainer.style.display = 'none';
      console.log('Load more button hidden');
    }
  }

  // Preload next batch of images for instant loading
  function preloadNextBatch() {
    if (!hasMorePhotos || isLoadingMore) return;

    // Use requestIdleCallback for CPU-friendly preloading
    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => {
        performPreload();
      }, { timeout: 2000 }); // Timeout after 2 seconds if no idle time
    } else {
      // Fallback for browsers without requestIdleCallback
      setTimeout(performPreload, 100);
    }

    function performPreload() {
      const nextOffset = currentOffset;
      const nextLimit = photosPerLoad;

      // Fetch next batch data without rendering
      const albumFilter = albumSelect.value || 'all';
      const sortBy = 'date';
      const sortOrder = 'desc';

      const url = `list.php?album=${encodeURIComponent(albumFilter)}&sort=${encodeURIComponent(sortBy)}&order=${encodeURIComponent(sortOrder)}&limit=${nextLimit}&offset=${nextOffset}`;

      // Get temporarily unlocked albums
      const unlockedAlbums = window.temporarilyUnlockedAlbums ? JSON.stringify(window.temporarilyUnlockedAlbums) : '{}';

      fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Unlocked-Albums': unlockedAlbums
        }
      })
      .then(r => r.json())
      .then(nextPhotos => {
        if (nextPhotos && nextPhotos.length > 0) {
          // Preload the first few images of the next batch
          const preloadCount = Math.min(3, nextPhotos.length);
          for (let i = 0; i < preloadCount; i++) {
            const imgSrc = nextPhotos[i].full_image || ("uploads/" + encodeURIComponent(nextPhotos[i].filename));
            const img = new Image();
            img.src = imgSrc;
          }
        }
      })
      .catch(error => {
        // Silently fail - preloading is not critical
      });
    }
  }

  // Reset pagination when filters change
  window.resetPagination = function() {
    currentOffset = 0;
    hasMorePhotos = true;
    isLoadingMore = false;
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
      // Error checking user feedback - silently fail
    });
  }

  // Admin Login Modal Functions - Make them globally accessible
  window.showLoginModal = function() {
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

  window.hideLoginModal = function() {
    const modal = document.getElementById('adminLoginModal');
    if (modal) modal.style.display = 'none';
  }

  console.log('Script loaded, waiting for DOMContentLoaded');
  // Performance logging for script execution
  const scriptStartTime = performance.now();
  console.log('🚀 Script execution started at:', scriptStartTime);

  document.addEventListener('DOMContentLoaded', function() {
    const domReadyTime = performance.now();
    console.log('📊 DOMContentLoaded fired at:', domReadyTime, 'ms from script start');
    console.log('⏱️ Time from script start to DOM ready:', (domReadyTime - scriptStartTime).toFixed(2), 'ms');
    const fileInput = document.getElementById("fileInput");
    const folderInput = document.getElementById("folderInput");
    const uploadBtn = document.getElementById("uploadBtn");
    const folderBtn = document.getElementById("folderBtn");
    const gallery = document.getElementById("gallery");
    const progressWrapper = document.getElementById("progressWrapper");
    const progressBar = document.getElementById("progressBar");
    const uploadStatus = document.getElementById("uploadStatus");
    const spinner = document.getElementById("spinner");
    const modal = document.getElementById("modal");
    const modalImg = document.getElementById("modalImg");
    const modalInfo = document.getElementById("modalInfo");
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

        // Check if we're currently viewing a private album that we've unlocked
        const currentAlbum = albumSelect.value;
        const selectedAlbum = albums.find(a => a.id == currentAlbum);

        if (currentAlbum && currentAlbum !== 'all' && selectedAlbum && selectedAlbum.password &&
            window.temporarilyUnlockedAlbums && window.temporarilyUnlockedAlbums[currentAlbum]) {
          // We're in a private album that we've unlocked - upload directly here
          console.log('🔓 Uploading directly to unlocked private album:', currentAlbum, '- Album name:', selectedAlbum.name);
          selectedAlbumForUpload = currentAlbum;
          proceedUpload();
        } else {
          // Normal flow - show album selection modal
          console.log('📂 Showing album selection modal for upload');
          loadAlbumsForUpload();
          uploadAlbumModal.style.display = "flex";
        }
      }

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
            // Handle "All Photos" selection - pass null for album_id
            const albumId = selectedAlbumForUpload === "all" ? null : selectedAlbumForUpload;
            await uploadOne(f, albumId);
            uploaded++;
            progressBar.style.width = (uploaded / pendingFiles.length * 100) + "%";
          }
          uploadStatus.style.display = "none";
          progressWrapper.style.display = "none";
          progressBar.style.width = "0%";

          // If uploading to a specific album (not "all"), automatically unlock it for viewing and switch to it
          if (selectedAlbumForUpload && selectedAlbumForUpload !== "all") {
            if (!window.temporarilyUnlockedAlbums) {
              window.temporarilyUnlockedAlbums = {};
            }
            window.temporarilyUnlockedAlbums[selectedAlbumForUpload] = true;
            console.log('🔓 Automatically unlocked album after upload:', selectedAlbumForUpload);

            // Switch to the uploaded album
            albumSelect.value = selectedAlbumForUpload;
            console.log('📂 Switched to uploaded album:', selectedAlbumForUpload);
          }

          loadGallery();
          fileInput.value = "";
          folderInput.value = "";
          pendingFiles = [];
          selectedAlbumForUpload = null;
        })();
      }

      window.cancelUpload = function() {
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
              const percentComplete = Math.round((e.loaded / e.total) * 100);
              console.log(`📊 Upload progress for ${file.name}: ${percentComplete}% (${Math.round(e.loaded / 1024)} KB / ${Math.round(e.total / 1024)} KB)`);
            }
          };

          xhr.onload = () => {
            if (xhr.status === 200) {
              console.log(`✅ Server response for ${file.name}: Success`);
            } else {
              console.warn(`⚠️ Server response for ${file.name}: HTTP ${xhr.status}`);
            }
            resolve();
          };

          xhr.onerror = () => {
            console.error(`❌ Upload failed for ${file.name}: Network error`);
            alert(`Upload thất bại: ${file.name}`);
            resolve();
          };

          console.log(`🔄 Starting upload for ${file.name} to album ID: ${albumId || 'none (All Photos)'}`);
          xhr.send(formData);
        });
      }


      // === Gallery with Pagination ===
      // Pagination variables are now global at the top of the script
      // Loads 35 photos initially, then 35 more with "Load More" button

      window.renderPhotos = function(photosData, append = false) {
        const renderStartTime = performance.now();
        console.log('🎨 Starting to render', photosData.length, 'photos at:', renderStartTime, append ? '(appending)' : '(initial)');

        if (!photos.length && !append) {
          gallery.innerHTML = '<div class="empty-state"><div class="empty-icon">📷</div><h3>No Photos Yet</h3><p>Upload some photos to get started!</p><button class="btn primary" onclick="document.getElementById(\'uploadBtn\').click()">Upload Photos</button></div>';
          return;
        }

        // Skip preloading for now to avoid warnings

        // Use requestAnimationFrame for better performance
        requestAnimationFrame(() => {
          // Pre-calculate common values to reduce computation
          const isAdminMode = document.body.classList.contains('admin');

          // Use document fragment for better performance
          const fragment = document.createDocumentFragment();

          // Process photos in batches to prevent blocking - smaller batches for faster initial render
          const batchSize = append ? 10 : 5; // Smaller batches for initial load
          let currentIndex = 0;

          function processBatch() {
            const endIndex = Math.min(currentIndex + batchSize, photosData.length);

            for (let i = currentIndex; i < endIndex; i++) {
              const p = photosData[i];
              const cardIndex = append ? (photos.length - photosData.length + i) : i;
              const card = createPhotoCardOptimized(p, cardIndex, isAdminMode);
              fragment.appendChild(card);
            }

            currentIndex = endIndex;

            if (currentIndex < photosData.length) {
              // Process next batch asynchronously
              setTimeout(processBatch, 0);
            } else {
              // All batches processed
              if (append) {
                // Append to existing gallery
                gallery.appendChild(fragment);
              } else {
                // Clear gallery and add new content
                while (gallery.firstChild) {
                  gallery.removeChild(gallery.firstChild);
                }
                gallery.appendChild(fragment);
              }

              const renderEndTime = performance.now();
              console.log('✅ Finished rendering photos at:', renderEndTime);
              console.log('⏱️ Total render time:', (renderEndTime - renderStartTime).toFixed(2), 'ms');
              console.log('📈 Average time per photo:', ((renderEndTime - renderStartTime) / photosData.length).toFixed(2), 'ms');
            }
          }

          // Start processing first batch
          processBatch();
        });
      }


      // Optimized photo card creation function
      window.createPhotoCardOptimized = function(p, index, isAdminMode) {
        const card = document.createElement("div");
        card.className = "photo-card";
        card.dataset.index = index;

        // Create responsive image element with blur placeholder
        const img = document.createElement("img");
        img.decoding = "async";
        img.alt = p.filename;
        img.dataset.full = p.full_image || ("uploads/" + encodeURIComponent(p.filename));

        // Priority loading for above-the-fold images (first 6 images)
        if (index < 6) {
            img.loading = "eager";
            img.fetchPriority = "high";
        } else {
            img.loading = "lazy";
            img.fetchPriority = "low";
        }

        img.style.cssText = 'width: 100%; height: 200px; object-fit: cover; display: block; opacity: 0; transition: opacity 0.3s ease;';

        // Set blur placeholder for better perceived performance
        if (p.blur_placeholder) {
            img.style.backgroundImage = `url(${p.blur_placeholder})`;
            img.style.backgroundSize = 'cover';
            img.style.backgroundPosition = 'center';
        }

        // Use direct image URLs for now (serve_image.php can be enabled later)
        const imageUrl = "uploads/" + encodeURIComponent(p.filename);

        img.src = imageUrl;
        img.srcset = `${imageUrl} 800w`;
        img.sizes = '800px';

        // Handle image load for smooth transitions
        img.onload = function() {
            this.style.opacity = '1';
            this.style.backgroundImage = 'none'; // Remove blur placeholder
        };

        // Error handling
        img.onerror = function() {
            console.warn('Failed to load image:', this.src);
            // Fallback to original image
            if (this.src !== "uploads/" + encodeURIComponent(p.filename)) {
                this.src = "uploads/" + encodeURIComponent(p.filename);
            } else {
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
            }
        };

        // Add WebP support if available
        if (p.thumb_format === 'webp') {
          // WebP is already the preferred format
        }

        // Simple error handling
        img.onerror = function() {
          console.warn('Failed to load image:', this.src);
          // Fallback to original image if thumbnail fails
          if (this.src !== "uploads/" + encodeURIComponent(p.filename)) {
            this.src = "uploads/" + encodeURIComponent(p.filename);
          } else {
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
          }
        };

        card.appendChild(img);

        // Add admin elements only if needed
        if (isAdminMode) {
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.className = "select-photo";
          checkbox.value = p.filename;
          card.appendChild(checkbox);

          const del = document.createElement("button");
          del.className = "delete-btn";
          del.textContent = "🗑️";
          del.onclick = () => deleteSelected([p.filename]);
          card.appendChild(del);
        }

        // Add camera overlay only if metadata exists
        if (p.metadata && Object.keys(p.metadata).length > 0) {
          const cameraInfo = getCameraInfo(p.metadata);
          if (cameraInfo.model !== 'Unknown Camera' || cameraInfo.aperture || cameraInfo.focal || cameraInfo.iso) {
            const cameraOverlay = document.createElement("div");
            cameraOverlay.className = "camera-overlay";

            let settingsHtml = '';
            if (cameraInfo.aperture || cameraInfo.focal || cameraInfo.iso) {
              settingsHtml = `<div class="camera-settings">${
                cameraInfo.aperture ? `<div class="setting"><div class="setting-label">f-stop</div><div class="setting-value">${cameraInfo.aperture}</div></div>` : ''
              }${
                cameraInfo.focal ? `<div class="setting"><div class="setting-label">focal</div><div class="setting-value">${cameraInfo.focal}</div></div>` : ''
              }${
                cameraInfo.iso ? `<div class="setting"><div class="setting-label">ISO</div><div class="setting-value">${cameraInfo.iso}</div></div>` : ''
              }</div>`;
            }

            cameraOverlay.innerHTML = `<div class="camera-model">${cameraInfo.model}</div>${settingsHtml}`;
            card.appendChild(cameraOverlay);
          }
        }

        // Add album tag only if different from default
        if (p.album_name && p.album_name !== 'General') {
          const albumTag = document.createElement("div");
          albumTag.className = "album-tag";
          albumTag.textContent = p.album_name;
          card.appendChild(albumTag);
        }

        return card;
      }

      window.loadGallery = function(skipAlbumReload = false, append = false) {
        if (isLoadingMore && append) return; // Prevent multiple simultaneous requests
        if (append) isLoadingMore = true;

        console.log('🎨 Starting to load gallery...', append ? '(appending)' : '(initial)');
        if (!append) spinner.style.display = "block";

        const albumFilter = albumSelect.value || 'all';
        const sortBy = 'date';
        const sortOrder = 'desc';

        // Use pagination for better performance
        const limit = append ? photosPerLoad : photosPerLoad;
        const offset = append ? currentOffset : 0;
        const url = `list.php?album=${encodeURIComponent(albumFilter)}&sort=${encodeURIComponent(sortBy)}&order=${encodeURIComponent(sortOrder)}&limit=${limit}&offset=${offset}`;

        // Prepare headers
        const headers = {
          'X-Requested-With': 'XMLHttpRequest'
        };

        // Send unlocked albums information for all gallery requests (needed for private album access)
        if (window.temporarilyUnlockedAlbums && Object.keys(window.temporarilyUnlockedAlbums).length > 0) {
          headers['X-Unlocked-Albums'] = JSON.stringify(window.temporarilyUnlockedAlbums);
          console.log('📤 Sending unlocked albums to backend:', window.temporarilyUnlockedAlbums);
        }

        // Also check session storage for unlocked albums (fallback)
        if (!headers['X-Unlocked-Albums']) {
          // Try to get from session via a quick API call
          fetch('get_unlocked_albums.php', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(r => r.json())
          .then(data => {
            if (data.unlocked_albums && Object.keys(data.unlocked_albums).length > 0) {
              headers['X-Unlocked-Albums'] = JSON.stringify(data.unlocked_albums);
              console.log('📤 Retrieved unlocked albums from session:', data.unlocked_albums);
            }
          })
          .catch(error => {
            console.log('Failed to retrieve unlocked albums from session:', error);
          });
        }

        fetch(url, {
          headers: headers
        })
        .then(r => r.json())
        .then(newPhotos => {
          console.log('✅ Gallery data loaded:', newPhotos.length, 'photos');

          if (append) {
            // Append new photos to existing array
            photos = photos.concat(newPhotos);
            currentOffset += newPhotos.length;
            hasMorePhotos = newPhotos.length >= photosPerLoad;
            renderPhotos(newPhotos, true); // Append mode
            isLoadingMore = false;
          } else {
            // Initial load - replace all photos
            photos = newPhotos;
            currentOffset = newPhotos.length;
            hasMorePhotos = newPhotos.length >= photosPerLoad;
            console.log('Initial load: photos.length =', photos.length, 'photosPerLoad =', photosPerLoad, 'hasMorePhotos =', hasMorePhotos);
            renderPhotos(newPhotos, false); // Replace mode
          }

          updatePhotoCount(photos.length);
          updateLoadMoreButton();

          if (!append) {
            spinner.style.display = "none";
            if (!skipAlbumReload) {
              loadAlbums();
            }
          }
        })
        .catch(error => {
          console.error('❌ Error loading gallery:', error);
          if (!append) spinner.style.display = "none";
          if (append) isLoadingMore = false;
          gallery.innerHTML = "<p class='empty'>Lỗi load gallery</p>";
        });
      }



      function createPhotoCard(p, index) {
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
        img.decoding = "async";
        img.alt = p.filename;
        img.dataset.full = "uploads/" + encodeURIComponent(p.filename);

        // Use original image instead of thumbnail (GD not available)
        img.src = "uploads/" + encodeURIComponent(p.filename);
        img.style.width = '100%';
        img.style.height = '200px';
        img.style.objectFit = 'cover';
        img.style.display = 'block';

        // Add error handling
        img.onerror = function() {
            console.error('Failed to load image:', this.src);
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
        };

        img.onload = function() {
            console.log('Image loaded successfully:', this.src);
        };

        card.appendChild(img);

        // Camera info overlay
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

        return card;
      }

      window.getSelectedFiles = function() {
        const checkboxes = document.querySelectorAll(".select-photo:checked");
        const files = [...checkboxes].map(cb => cb.value);
        return files;
      }

      window.deleteSelected = function(files) {
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
          alert("Lỗi kết nối khi xóa!");
        });
      }

function updateModalInfo(index) {
  const photo = photos[index];

  // Add fade-out animation before changing photo
  modalImg.classList.add('fade-out');
  modalImg.classList.remove('fade-in');

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

      // Add fade-in animation after image loads
      setTimeout(() => {
        modalImg.classList.remove('fade-out');
        modalImg.classList.add('fade-in');
      }, 50);

      resolve();
    };
    modalImg.onerror = () => {
      spinner.remove();
      modalImg.classList.remove('fade-out');
      modalImg.classList.add('fade-in');
      // Show a placeholder or error message instead of rejecting
      modalImg.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBub3QgZm91bmQ8L3RleHQ+PC9zdmc+';
      resolve(); // Resolve instead of reject to prevent unhandled promise
    };
    // Use direct image URL for viewing
    const fullImageSrc = "uploads/" + encodeURIComponent(photo.filename);
    modalImg.src = fullImageSrc;
  });

  // Reset rating and initialize stars
  currentRating = 0;

  // Add fade-out animation to info panel
  modalInfo.classList.add('fade-out');
  modalInfo.classList.remove('fade-in');

  setTimeout(() => {
    initRatingStars();
    loadFeedbackStats(photo.filename);

    // Fade info panel back in after content updates
    setTimeout(() => {
      modalInfo.classList.remove('fade-out');
      modalInfo.classList.add('fade-in');
    }, 150);
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

window.formatShutterSpeed = function(exposureTime) {
  if (!exposureTime) return "Unknown";


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

window.formatDate = function(dateString) {
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

window.getCameraInfo = function(metadata) {
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


  // Format ISO nicely - try multiple field names
  const iso = exif.ISOSpeedRatings || exif.ISO || exif.ISOSpeed || null;


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
  const maxPreload = 4; // Preload up to 4 adjacent images

  // Preload next images (higher priority)
  for (let i = 1; i <= maxPreload && currentIndex + i < photos.length; i++) {
    const nextSrc = "uploads/" + encodeURIComponent(photos[currentIndex + i].filename);
    preloadPromises.push(preloadImage(nextSrc));
  }

  // Preload previous images (lower priority)
  for (let i = 1; i <= 2 && currentIndex - i >= 0; i++) {
    const prevSrc = "uploads/" + encodeURIComponent(photos[currentIndex - i].filename);
    preloadPromises.push(preloadImage(prevSrc));
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

  // If select mode is enabled, toggle checkbox instead of opening modal
  if (selectModeEnabled && document.body.classList.contains('admin')) {
    const checkbox = card.querySelector('.select-photo');
    if (checkbox) {
      // Provide haptic feedback on mobile devices
      if (navigator.vibrate && 'ontouchstart' in window) {
        navigator.vibrate(10);
      }

      checkbox.checked = !checkbox.checked;
      e.preventDefault();
      console.log(`Photo ${checkbox.checked ? 'selected' : 'deselected'}`);
      return;
    }
  }

  e.preventDefault();
  currentPhotoIndex = parseInt(card.dataset.index, 10);
  modal.style.display = "flex";

  // Start preloading adjacent images immediately
  preloadAdjacentImages(currentPhotoIndex);

  updateModalInfo(currentPhotoIndex);
});


// Photo navigation with animations
function navigatePhoto(direction) {
  let newIndex = currentPhotoIndex;

  if (direction === 'prev' && currentPhotoIndex > 0) {
    newIndex = currentPhotoIndex - 1;
    // Add slide animation for previous photo
    modalImg.classList.add('slide-right');
    modalImg.classList.remove('slide-center');
  } else if (direction === 'next' && currentPhotoIndex < photos.length - 1) {
    newIndex = currentPhotoIndex + 1;
    // Add slide animation for next photo
    modalImg.classList.add('slide-left');
    modalImg.classList.remove('slide-center');
  }

  if (newIndex !== currentPhotoIndex) {
    currentPhotoIndex = newIndex;
    // Preload images for the new position
    preloadAdjacentImages(currentPhotoIndex);

    // Animate to center after a brief delay
    setTimeout(() => {
      modalImg.classList.remove('slide-left', 'slide-right');
      modalImg.classList.add('slide-center');
      updateModalInfo(currentPhotoIndex);
    }, 150);
  }
}

// Global keyboard event listener for F12 and other keys
document.addEventListener("keydown", (e) => {
  if (e.key === "F12") {
    if (!document.body.classList.contains('admin')) {
      e.preventDefault();
      alert('Developer tools are disabled for regular users.');
      return;
    }
    // Allow dev tools for admin
    return;
  }

  if (modal.style.display !== "flex") return;

  if (e.key === "ArrowLeft") {
    e.preventDefault();
    navigatePhoto('prev');
  } else if (e.key === "ArrowRight") {
    e.preventDefault();
    navigatePhoto('next');
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

// Swipe controls for mobile devices in modal
modal.addEventListener('touchstart', (e) => {
  if (e.touches.length === 1) {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    isSwiping = true;
  }
}, { passive: true });

modal.addEventListener('touchmove', (e) => {
  if (!isSwiping || e.touches.length !== 1) return;

  const touch = e.touches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;

  // If vertical movement is greater than horizontal, don't treat as swipe
  if (Math.abs(deltaY) > Math.abs(deltaX)) {
    isSwiping = false;
    return;
  }

  // Prevent default if it's a horizontal swipe
  if (Math.abs(deltaX) > 10) {
    e.preventDefault();
  }
}, { passive: false });

modal.addEventListener('touchend', (e) => {
  if (!isSwiping) return;

  const touchEndX = e.changedTouches[0].clientX;
  const touchEndY = e.changedTouches[0].clientY;
  const deltaX = touchEndX - touchStartX;
  const deltaY = touchEndY - touchStartY;

  // Check if it's a valid swipe: horizontal distance > 50px, vertical < 30px
  if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 30) {
    if (deltaX > 0 && currentPhotoIndex > 0) {
      // Swipe right - previous photo
      currentPhotoIndex--;
      preloadAdjacentImages(currentPhotoIndex);
      updateModalInfo(currentPhotoIndex);
    } else if (deltaX < 0 && currentPhotoIndex < photos.length - 1) {
      // Swipe left - next photo
      currentPhotoIndex++;
      preloadAdjacentImages(currentPhotoIndex);
      updateModalInfo(currentPhotoIndex);
    }
  }

  isSwiping = false;
}, { passive: true });

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

          // Add lock icon for password-protected albums
          const lockIcon = album.password ? '🔒 ' : '';
          option.textContent = lockIcon + (album.name || 'Unknown');

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
          const passwordDisplay = album.password ? `<br><small style="color: #666;">Mật khẩu: "${album.password}"</small>` : '';
          div.innerHTML = `
            <div>
              <strong>${album.name || 'Unknown'}</strong>
              <br><small>${album.description || 'Không có mô tả'}</small>
              ${passwordDisplay}
            </div>
            <div>
              <button onclick="editAlbum(${album.id})">Sửa</button>
              <button onclick="deleteAlbum(${album.id})" style="background: #dc3545;">Xóa</button>
            </div>
          `;
          albumList.appendChild(div);
        });
      }

      window.createAlbum = function() {
        const name = document.getElementById("albumName").value.trim();
        const desc = document.getElementById("albumDesc").value.trim();
        const password = document.getElementById("albumPassword").value.trim();

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
          body: JSON.stringify({ name, description: desc, password: password || null, csrf: CSRF_TOKEN })
        })
        .then(r => r.json())
        .then(resp => {
          if (resp.success) {
            loadAlbums();
            document.getElementById("albumName").value = "";
            document.getElementById("albumDesc").value = "";
            document.getElementById("albumPassword").value = "";
          } else {
            alert("Lỗi tạo album: " + resp.error);
          }
        });
      }

      window.deleteAlbum = function(id) {
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

      window.editAlbum = function(id) {
        const album = albums.find(a => a.id == id);
        if (!album) return;

        const newName = prompt("Tên album mới:", album.name);
        if (!newName) return;

        const newDesc = prompt("Mô tả mới:", album.description || "");

        // Show current password if it exists
        const currentPassword = album.password || "";
        const passwordPrompt = currentPassword ?
          `Mật khẩu hiện tại: "${currentPassword}"\n\nNhập mật khẩu mới (để trống để giữ nguyên):` :
          "Nhập mật khẩu mới (tùy chọn):";
        const newPassword = prompt(passwordPrompt, currentPassword);

        fetch("albums.php", {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            id,
            name: newName,
            description: newDesc,
            password: newPassword || null,
            csrf: CSRF_TOKEN
          })
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
          console.error('❌ No album selected for upload');
          alert("Vui lòng chọn album!");
          return;
        }

        // Get album name for logging
        const albumInfo = albums.find(a => a.id == selectedAlbumForUpload);
        const albumName = albumInfo ? albumInfo.name : (selectedAlbumForUpload === 'all' ? 'All Photos' : 'Unknown');
        const isPrivate = albumInfo && albumInfo.password ? '🔒 Private' : '🔓 Public';

        console.log('🚀 Starting upload process via album selection modal...');
        console.log('📁 Target album:', selectedAlbumForUpload, '-', albumName, '-', isPrivate);
        console.log('📄 Files to upload:', pendingFiles.length, 'files');

        uploadAlbumModal.style.display = "none";
        let uploaded = 0;
        uploadStatus.style.display = "block";
        progressWrapper.style.display = "block";

        (async () => {
          for (const f of pendingFiles) {
            console.log(`📤 Uploading file ${uploaded + 1}/${pendingFiles.length}:`, f.name, `(${Math.round(f.size / 1024)} KB)`);
            uploadStatus.textContent = `Đang upload: ${f.name}`;

            // Handle "All Photos" selection - pass null for album_id
            const albumId = selectedAlbumForUpload === "all" ? null : selectedAlbumForUpload;
            await uploadOne(f, albumId);
            uploaded++;
            const progressPercent = (uploaded / pendingFiles.length * 100);
            progressBar.style.width = progressPercent + "%";
            console.log(`✅ File ${uploaded}/${pendingFiles.length} uploaded successfully - Progress: ${Math.round(progressPercent)}%`);
          }

          console.log('🎉 Upload process completed!');
          console.log('📊 Summary:', uploaded, 'files uploaded to album:', selectedAlbumForUpload, '-', albumName);

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
          alert("Lỗi kết nối khi chuyển ảnh!");
        });
      };

      window.cancelMoveToAlbum = function() {
        moveAlbumModal.style.display = "none";
        selectedPhotosForMove = [];
      };

      window.closeAlbumModal = function() {
        albumModal.style.display = "none";
      }

      // Album password functionality
      window.showAlbumPasswordModal = function(albumId, albumName) {
        console.log('🔐 Showing password modal for album:', albumId, albumName);
        document.getElementById('albumPasswordName').textContent = albumName;
        document.getElementById('albumPasswordInput').value = '';
        document.getElementById('passwordError').style.display = 'none';
        document.getElementById('albumPasswordModal').style.display = 'flex';
        document.getElementById('albumPasswordInput').focus();

        // Store album ID for verification
        window.currentAlbumForPassword = albumId;
        console.log('💾 Stored currentAlbumForPassword:', window.currentAlbumForPassword);
      };

      window.hideAlbumPasswordModal = function() {
        document.getElementById('albumPasswordModal').style.display = 'none';
        window.currentAlbumForPassword = null;
      };

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
          const password = document.getElementById("albumPassword").value.trim();

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
            body: JSON.stringify({ name, description: desc, password: password || null, csrf: CSRF_TOKEN })
          })
          .then(r => r.json())
          .then(resp => {
            if (resp.success) {
              loadAlbums();
              document.getElementById("albumName").value = "";
              document.getElementById("albumDesc").value = "";
              document.getElementById("albumPassword").value = "";
            } else {
              alert("Lỗi tạo album: " + resp.error);
            }
          })
          .catch(error => {
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

      // Lọc theo album
      albumSelect.addEventListener("change", () => {
        const albumFilter = albumSelect.value;
        console.log('🎯 Album select changed to:', albumFilter);
        console.log('📋 Available albums:', albums);
        console.log('🔓 Temporarily unlocked albums:', window.temporarilyUnlockedAlbums);

        // Store unlocked albums in session for backend access
        if (window.temporarilyUnlockedAlbums) {
          fetch('store_unlocked_albums.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              unlocked_albums: window.temporarilyUnlockedAlbums,
              csrf: CSRF_TOKEN
            })
          }).catch(error => {
            console.log('Failed to store unlocked albums:', error);
          });
        }

        // "All Photos" album is always accessible - no password check needed
        if (albumFilter === 'all') {
          console.log('📸 Loading all photos');
          // Reset pagination when changing album
          resetPagination();
          // Reload gallery immediately for "All Photos"
          loadGallery();
          return;
        }

        // Check if album is password protected (only for specific albums)
        if (albumFilter !== '') {
          const selectedAlbum = albums.find(a => a.id == albumFilter);
          console.log('🔍 Selected album:', selectedAlbum);
          if (selectedAlbum && selectedAlbum.password) {
            console.log('🔒 Album is password protected');
            // Check if this album is temporarily unlocked in this session
            if (!window.temporarilyUnlockedAlbums || !window.temporarilyUnlockedAlbums[albumFilter]) {
              console.log('🚫 Album not unlocked, showing password modal');
              showAlbumPasswordModal(albumFilter, selectedAlbum.name);
              return; // Don't load gallery yet
            } else {
              console.log('✅ Album is unlocked, proceeding to load gallery');
            }
          } else {
            console.log('🔓 Album is not password protected');
          }
        }

        // Reset pagination when changing album
        resetPagination();
        // Reload gallery when changing album selection to show photos from selected album
        console.log('📥 Loading gallery for album:', albumFilter);
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


      // Handle album password form submission
      window.handleAlbumPasswordSubmit = function(e) {
        e.preventDefault();

        const password = document.getElementById('albumPasswordInput').value;
        const submitBtn = document.getElementById('albumPasswordSubmitBtn');
        const errorDiv = document.getElementById('passwordError');

        console.log('🔑 Submitting password for album:', window.currentAlbumForPassword);

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang kiểm tra...';

        // Hide previous error
        errorDiv.style.display = 'none';

        // Send password verification request
        fetch('albums.php?verify_password=1', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            album_id: window.currentAlbumForPassword,
            password: password,
            csrf: CSRF_TOKEN
          })
        })
        .then(response => {
          // Check if response is ok before parsing JSON
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            // Password correct - temporarily unlock this album for this session only
            // Don't store in localStorage - each album requires its own password entry
            if (!window.temporarilyUnlockedAlbums) {
              window.temporarilyUnlockedAlbums = {};
            }

            // Save album ID before hiding modal (which clears it)
            const unlockedAlbumId = window.currentAlbumForPassword;
            window.temporarilyUnlockedAlbums[unlockedAlbumId] = true;

            hideAlbumPasswordModal();
            // Directly switch to the unlocked album without relying on change event
            console.log('🔓 Album unlocked:', unlockedAlbumId);
            console.log('📋 Temporarily unlocked albums:', window.temporarilyUnlockedAlbums);

            // Update album select value
            albumSelect.value = unlockedAlbumId;

            // Directly execute album change logic
            console.log('🎯 Directly switching to album:', unlockedAlbumId);

            // Reset pagination when changing album
            resetPagination();
            // Reload gallery when changing album selection to show photos from selected album
            console.log('📥 Loading gallery for unlocked album:', unlockedAlbumId);
            loadGallery();
          } else {
            // Password incorrect
            errorDiv.innerHTML = `
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 17h.01" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10.29 3h3.42l7 12.12A2 2 0 0 1 19.7 19H4.3a2 2 0 0 1-1.01-3.88L10.29 3z" stroke="#ffb4b4" stroke-width="0" fill="rgba(220,38,38,0.14)"/>
              </svg>
              <div>Mật khẩu không đúng!</div>
            `;
            errorDiv.style.display = 'flex';

            // Add shake animation
            const form = document.getElementById('albumPasswordForm');
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 700);
          }
        })
        .catch(error => {
          errorDiv.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="#ffb4b4" stroke-width="2"/>
            </svg>
            <div>Lỗi kết nối. Vui lòng thử lại.</div>
          `;
          errorDiv.style.display = 'flex';
        })
        .finally(() => {
          // Re-enable submit button
          submitBtn.disabled = false;
          submitBtn.textContent = 'Mở Album';
        });
      };

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
            backupToWayback(); // Auto backup on login
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

      // Close album password modal when clicking outside
      document.getElementById('albumPasswordModal').addEventListener('click', function(e) {
        if (e.target === this) {
          hideAlbumPasswordModal();
        }
      });

      // Close modal when clicking outside
      document.getElementById('adminLoginModal').addEventListener('click', function(e) {
        if (e.target === this) {
          hideLoginModal();
        }
      });

      // Private Gallery Modal Functions
      function showPrivateGalleryModal() {
        document.getElementById('privateGalleryModal').style.display = 'flex';
        document.getElementById('privateGalleryPassword').focus();
        document.getElementById('privateGalleryError').style.display = 'none';
      }

      function closePrivateGalleryModal() {
        document.getElementById('privateGalleryModal').style.display = 'none';
        document.getElementById('privateGalleryPassword').value = '';
      }

      // Make functions globally accessible
      window.showPrivateGalleryModal = showPrivateGalleryModal;
      window.closePrivateGalleryModal = closePrivateGalleryModal;

      // Handle private gallery password submission
      document.getElementById('privateGalleryForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const password = document.getElementById('privateGalleryPassword').value;
        const errorDiv = document.getElementById('privateGalleryError');

        if (!password.trim()) {
          errorDiv.textContent = 'Please enter a password';
          errorDiv.style.display = 'block';
          return;
        }

        // Submit password for verification
        fetch('verify_private_gallery.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ password: password })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Password correct - redirect to private gallery
            window.location.href = '/admin-gallery.php?access_granted=1';
          } else {
            errorDiv.textContent = data.error || 'Invalid password';
            errorDiv.style.display = 'block';

            // Shake animation
            const form = document.getElementById('privateGalleryForm');
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 700);
          }
        })
        .catch(error => {
          errorDiv.textContent = 'Connection error. Please try again.';
          errorDiv.style.display = 'block';
        });
      });

      // Close private gallery modal when clicking outside
      document.getElementById('privateGalleryModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closePrivateGalleryModal();
        }
      });


      window.toggleSelectMode = function() {
        selectModeEnabled = !selectModeEnabled;
        localStorage.setItem('selectModeEnabled', selectModeEnabled);
        updateSelectModeButton();

        if (selectModeEnabled) {
          initSelectMode();
        } else {
          updateCheckboxVisibility();
        }

        // Show feedback
        const status = selectModeEnabled ? 'enabled' : 'disabled';
        console.log(`🎯 Select mode has been ${status}`);
        alert(`Select mode has been ${status}!`);
      };

      window.deselectAllPhotos = function() {
        const allCheckboxes = document.querySelectorAll('.select-photo');
        allCheckboxes.forEach(checkbox => {
          checkbox.checked = false;
        });
        console.log('Deselected all photos');
      };

      // Add keyboard shortcuts for better accessibility
      document.addEventListener('keydown', function(e) {
        // Only handle shortcuts when not typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.contentEditable === 'true') {
          return;
        }

        // Ctrl+A or Cmd+A to select all photos (when select mode is on)
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && selectModeEnabled) {
          e.preventDefault();
          const allCheckboxes = document.querySelectorAll('.select-photo');
          const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);

          allCheckboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
          });

          console.log(`${allChecked ? 'Deselected' : 'Selected'} all photos via keyboard`);
        }

        // Escape key to exit select mode
        if (e.key === 'Escape' && selectModeEnabled) {
          toggleSelectMode();
        }
      });

      window.updateSelectModeButton = function() {
        const selectModeBtn = document.getElementById('selectModeToggle');
        const selectModeIcon = document.getElementById('selectModeIcon');
        const selectModeText = document.getElementById('selectModeText');

        if (selectModeEnabled) {
          selectModeBtn.classList.add('active');
          selectModeIcon.textContent = '☑️';
          selectModeText.textContent = 'Select Mode: ON';
        } else {
          selectModeBtn.classList.remove('active');
          selectModeIcon.textContent = '👆';
          selectModeText.textContent = 'Select Mode: OFF';
        }
      };


      // Initialize toggle buttons if admin
      if (document.body.classList.contains('admin')) {
        updateSelectModeButton();
        if (selectModeEnabled) {
          initSelectMode();
        }
      }

      // Close modal on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('adminLoginModal').style.display === 'flex') {
          hideLoginModal();
        }
      });

      // === Select Mode Functionality (Admin Only) ===

      function initSelectMode() {
        if (!document.body.classList.contains('admin')) {
          console.log('Select mode disabled: Not in admin mode');
          return;
        }

        const gallery = document.getElementById('gallery');
        if (!gallery) {
          console.log('Select mode disabled: Gallery element not found');
          return;
        }

        console.log('Select mode initialized for admin mode');

        // Update checkbox visibility based on select mode
        updateCheckboxVisibility();

        // Add touch support for mobile devices
        if ('ontouchstart' in window) {
          console.log('Touch device detected - adding touch event listeners for select mode');

          // Add touch feedback for photo cards
          const photoCards = gallery.querySelectorAll('.photo-card');
          photoCards.forEach(card => {
            card.addEventListener('touchstart', function(e) {
              if (!selectModeEnabled) return;
              this.style.transform = 'scale(0.98)';
              this.style.transition = 'transform 0.1s ease';
            }, { passive: true });

            card.addEventListener('touchend', function(e) {
              if (!selectModeEnabled) return;
              this.style.transform = '';
              this.style.transition = 'transform 0.1s ease';
            }, { passive: true });
          });
        }
      }

      function updateCheckboxVisibility() {
        const checkboxes = document.querySelectorAll('.select-photo');
        const deselectBtn = document.getElementById('deselectAllBtn');

        checkboxes.forEach(checkbox => {
          if (selectModeEnabled) {
            checkbox.style.display = 'block';
          } else {
            checkbox.style.display = 'none';
          }
        });

        if (deselectBtn) {
          deselectBtn.style.display = selectModeEnabled ? 'inline-block' : 'none';
        }
      }

      // === Optimized Lazy Loading ===
      function initAdvancedLazyLoading() {
        // Use native lazy loading when available (much better performance)
        if ('loading' in HTMLImageElement.prototype) {
          console.log('Using native lazy loading');
          return; // Native lazy loading is already applied via img.loading = "lazy"
        }

        // Fallback Intersection Observer for older browsers
        if ('IntersectionObserver' in window) {
          const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.dataset.src;

                if (src && !img.classList.contains('loaded')) {
                  // Check if image exists before loading
                  const tempImg = new Image();
                  const timeout = setTimeout(() => {
                    console.warn('Lazy load timeout for:', src);
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
                    observer.unobserve(img);
                  }, 3000);

                  tempImg.onload = () => {
                    clearTimeout(timeout);
                    img.src = src;
                    img.classList.add('loaded');
                    img.classList.remove('lazy-image');
                    img.style.filter = 'none';
                    observer.unobserve(img);
                  };

                  tempImg.onerror = () => {
                    clearTimeout(timeout);
                    console.warn('Failed to lazy load image:', src);
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
                    observer.unobserve(img);
                  };

                  tempImg.src = src;
                }
              }
            });
          }, {
            rootMargin: '200px 0px', // Increased for earlier loading
            threshold: 0.1 // Higher threshold for more reliable detection
          });

          // Apply to lazy images only
          document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
          });

          console.log('Intersection Observer lazy loading initialized');
        } else {
          // Minimal fallback
          console.log('Using minimal lazy loading fallback');
          loadImagesOnScroll();
        }
      }

      function loadImagesOnScroll() {
        // Optimized scroll-based lazy loading with throttling
        let scrollTimeout;
        const viewportHeight = window.innerHeight;

        function loadVisibleImages() {
          // Throttle scroll events
          if (scrollTimeout) return;

          scrollTimeout = setTimeout(() => {
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
              if (img.dataset.src && !img.classList.contains('loaded')) {
                const rect = img.getBoundingClientRect();
                if (rect.top < viewportHeight + 50 && rect.bottom > -50) {
                  // Check if image exists before loading
                  const tempImg = new Image();
                  const timeout = setTimeout(() => {
                    console.warn('Scroll load timeout for:', img.dataset.src);
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
                  }, 2000);

                  tempImg.onload = () => {
                    clearTimeout(timeout);
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    img.style.filter = 'none';
                  };

                  tempImg.onerror = () => {
                    clearTimeout(timeout);
                    console.warn('Failed to scroll load image:', img.dataset.src);
                    img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjQ0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4=';
                  };

                  tempImg.src = img.dataset.src;
                }
              }
            });
            scrollTimeout = null;
          }, 16); // ~60fps
        }

        // Use passive listeners for better performance
        window.addEventListener('scroll', loadVisibleImages, { passive: true });
        window.addEventListener('resize', loadVisibleImages, { passive: true });

        // Load initially visible images
        loadVisibleImages();
      }

      // === Swipe Controls for Mobile Devices ===
      let touchStartX = 0;
      let touchStartY = 0;
      let touchEndX = 0;
      let touchEndY = 0;

      function initSwipeControls() {
        const modal = document.getElementById('modal');
        if (!modal) {
          console.log('Swipe controls disabled: Modal element not found');
          return;
        }

        console.log('Swipe controls initialized for mobile devices');

        // Add touch event listeners to the modal content area (not just modal background)
        const modalContent = modal.querySelector('div');
        if (modalContent) {
          modalContent.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            console.log('Touch start detected:', touchStartX, touchStartY);
          }, { passive: false });

          modalContent.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].clientX;
            touchEndY = e.changedTouches[0].clientY;
            console.log('Touch end detected:', touchEndX, touchEndY);
            handleSwipe();
          }, { passive: false });
        }

        // Also add to the image container for better touch detection
        const imageContainer = modal.querySelector('div > div:last-child');
        if (imageContainer) {
          imageContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
          }, { passive: false });

          imageContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].clientX;
            touchEndY = e.changedTouches[0].clientY;
            handleSwipe();
          }, { passive: false });
        }
      }

      function handleSwipe() {
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        console.log('Swipe detected - Delta X:', deltaX, 'Delta Y:', deltaY);

        // Only handle horizontal swipes with minimal vertical movement
        if (absDeltaX > 30 && absDeltaY < 50) {  // Reduced threshold for easier swiping
          if (deltaX > 0) {
            // Swipe right - previous photo
            console.log('Swipe right - going to previous photo');
            navigatePhoto('prev');
          } else {
            // Swipe left - next photo
            console.log('Swipe left - going to next photo');
            navigatePhoto('next');
          }
        } else {
          console.log('Swipe not recognized as horizontal - X:', absDeltaX, 'Y:', absDeltaY);
        }
      }


      // Initialize new features
      console.log('Initializing device-compatible features...');

      // Detect device capabilities
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      const isMobile = window.innerWidth <= 768;
      const isSmallScreen = window.innerWidth <= 480;

      console.log('Device detection:', {
        touch: isTouchDevice,
        mobile: isMobile,
        smallScreen: isSmallScreen,
        screenSize: `${window.innerWidth}x${window.innerHeight}`
      });

      // Initialize header toggle functionality
      console.log('Initializing header toggle functionality');

      // Set single column layout for mobile devices
      if (isMobile) {
        const gallery = document.getElementById('gallery');
        if (gallery) {
          gallery.style.gridTemplateColumns = '1fr';
          console.log('Mobile device detected - setting single column layout');
        }
      }

      initSwipeControls();
    
      // Add viewport meta tag for better mobile experience if not present
      if (!document.querySelector('meta[name="viewport"]')) {
        const viewport = document.createElement('meta');
        viewport.name = 'viewport';
        viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        document.head.appendChild(viewport);
      }
    
      // Initialize advanced lazy loading with Intersection Observer
      initAdvancedLazyLoading();
    
      // Initialize performance monitoring
      initPerformanceMonitoring();
    
      console.log('Device-compatible features initialized');
    
      loadGallery();

      // Auto daily backup for admin
      if (document.body.classList.contains('admin')) {
        const lastBackup = localStorage.getItem('lastWaybackBackup');
        const now = Date.now();
        const oneDay = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

        if (!lastBackup || (now - parseInt(lastBackup)) > oneDay) {
          console.log('Auto daily backup to Wayback Machine');
          backupToWayback();
          localStorage.setItem('lastWaybackBackup', now.toString());
        }
      }

   // Performance monitoring functions
    function initPerformanceMonitoring() {
      // Track Core Web Vitals
      if ('web-vitals' in window) {
        // If web-vitals library is loaded, use it
        webVitals.getLCP(sendToAnalytics);
        webVitals.getFID(sendToAnalytics);
        webVitals.getCLS(sendToAnalytics);
        webVitals.getFCP(sendToAnalytics);
        webVitals.getTTFB(sendToAnalytics);
      } else {
        // Fallback implementation
        trackCoreWebVitals();
      }
    
      // Track custom performance metrics
      trackCustomMetrics();
    }
    
    function sendToAnalytics(metric) {
      const data = {
        name: metric.name,
        value: metric.value,
        metadata: {
          rating: metric.rating,
          delta: metric.delta,
          id: metric.id
        }
      };
    
      // Send to server
      fetch('performance_monitor.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ performance_data: [data] })
      }).catch(error => {
        console.log('Failed to send performance data:', error);
      });
    }
    
    function trackCoreWebVitals() {
      // Fallback Core Web Vitals tracking
      let lcpValue = 0;
      let clsValue = 0;
      let fidValue = 0;
    
      // Track LCP (Largest Contentful Paint)
      new PerformanceObserver((list) => {
        const entries = list.getEntries();
        const lastEntry = entries[entries.length - 1];
        lcpValue = lastEntry.startTime;
        sendToAnalytics({
          name: 'LCP',
          value: lcpValue,
          rating: lcpValue <= 2500 ? 'good' : lcpValue <= 4000 ? 'needs-improvement' : 'poor'
        });
      }).observe({ entryTypes: ['largest-contentful-paint'] });
    
      // Track CLS (Cumulative Layout Shift)
      new PerformanceObserver((list) => {
        let clsValue = 0;
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput) {
            clsValue += entry.value;
          }
        }
        sendToAnalytics({
          name: 'CLS',
          value: clsValue,
          rating: clsValue <= 0.1 ? 'good' : clsValue <= 0.25 ? 'needs-improvement' : 'poor'
        });
      }).observe({ entryTypes: ['layout-shift'] });
    
      // Track FID (First Input Delay)
      new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          fidValue = entry.processingStart - entry.startTime;
          sendToAnalytics({
            name: 'FID',
            value: fidValue,
            rating: fidValue <= 100 ? 'good' : fidValue <= 300 ? 'needs-improvement' : 'poor'
          });
        }
      }).observe({ entryTypes: ['first-input'] });
    
      // Track FCP (First Contentful Paint)
      new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          sendToAnalytics({
            name: 'FCP',
            value: entry.startTime,
            rating: entry.startTime <= 1800 ? 'good' : entry.startTime <= 3000 ? 'needs-improvement' : 'poor'
          });
        }
      }).observe({ entryTypes: ['paint'] });
    }
    
    function trackCustomMetrics() {
      // Track image loading performance
      const images = document.querySelectorAll('img');
      images.forEach(img => {
        img.addEventListener('load', function() {
          const loadTime = performance.now() - (img.dataset.loadStart || 0);
          if (loadTime > 0) {
            sendToAnalytics({
              name: 'ImageLoadTime',
              value: loadTime,
              metadata: {
                src: img.src,
                size: img.naturalWidth + 'x' + img.naturalHeight
              }
            });
          }
        });
    
        img.addEventListener('error', function() {
          sendToAnalytics({
            name: 'ImageLoadError',
            value: 1,
            metadata: {
              src: img.src
            }
          });
        });
    
        // Mark load start time
        if (img.src) {
          img.dataset.loadStart = performance.now();
        }
      });
    
      // Track page load metrics
      window.addEventListener('load', function() {
        setTimeout(() => {
          const perfData = performance.getEntriesByType('navigation')[0];
          if (perfData) {
            sendToAnalytics({
              name: 'TimeToFirstByte',
              value: perfData.responseStart - perfData.requestStart
            });
    
            sendToAnalytics({
              name: 'DOMContentLoaded',
              value: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart
            });
    
            sendToAnalytics({
              name: 'PageLoadComplete',
              value: perfData.loadEventEnd - perfData.loadEventStart
            });
          }
        }, 0);
      });
    
      // Track gallery loading performance
      window.trackGalleryLoad = function(photoCount, loadTime) {
        sendToAnalytics({
          name: 'GalleryLoadTime',
          value: loadTime,
          metadata: {
            photoCount: photoCount
          }
        });
      };
    }
    });

    // Log script performance summary
    const scriptEndTime = performance.now();
    console.log('🏁 Script fully loaded and initialized at:', scriptEndTime);
    console.log('⏱️ Total script execution time:', (scriptEndTime - scriptStartTime).toFixed(2), 'ms');

  </script>
</body>
</html>

