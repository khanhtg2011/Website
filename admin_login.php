// This file has been consolidated into index.php
// Redirect to main gallery
header("Location: index.php");
exit;

// Thay đổi tên user / pass tại đây (hoặc đọc từ env trong production)
$ADMIN_USER = "Khanh";
$ADMIN_PASS = "0109211";

// Khởi tạo attempts
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // simple server-side check
    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        $_SESSION['username'] = $user;
        $_SESSION['login_attempts'] = 0;
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 3) {
            // lockout / kick back to index
            $_SESSION['login_attempts'] = 0;
            header("Location: index.php");
            exit;
        }
        $error = "Sai tài khoản hoặc mật khẩu! (Lần ".$_SESSION['login_attempts']."/3)";
    }
}

// attempts left for display
$attempts_left = 3 - ($_SESSION['login_attempts'] ?? 0);
?>
<!doctype html>
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

  /* floating decorative circles */
  .orb{ position:absolute; border-radius:50%; filter:blur(36px); opacity:.18; }
  .orb.one{ width:420px;height:420px; left:-80px; top:-120px; background:linear-gradient(90deg,#06b6d4,#3b82f6); animation: floaty 8s ease-in-out infinite; }
  .orb.two{ width:300px;height:300px; right:-100px; bottom:-140px; background:linear-gradient(90deg,#06b6d4,#10b981); animation: floaty2 9s ease-in-out infinite; }

  @keyframes floaty { 0%{transform:translateY(0) rotate(0deg)} 50%{transform:translateY(18px) rotate(8deg)} 100%{transform:translateY(0) rotate(0deg)} }
  @keyframes floaty2{ 0%{transform:translateY(0) rotate(0deg)} 50%{transform:translateY(-18px) rotate(-6deg)} 100%{transform:translateY(0) rotate(0deg)} }

  .wrap{
    width:100%; max-width:980px; display:grid; grid-template-columns: 420px 1fr; gap:28px;
    align-items:center; z-index:2;
  }

  /* left pane - illustration + title */
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

  /* card login */
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

  /* responsive */
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

    <div class="card" role="region" aria-label="Admin login card">
      <?php if($error): ?>
        <div class="error-box" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M12 9v4" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17h.01" stroke="#ffb4b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.29 3h3.42l7 12.12A2 2 0 0 1 19.7 19H4.3a2 2 0 0 1-1.01-3.88L10.29 3z" stroke="#ffb4b4" stroke-width="0" fill="rgba(220,38,38,0.14)"/></svg>
          <div><?=htmlspecialchars($error)?></div>
        </div>
      <?php endif; ?>

      <form method="post" style="display:flex; flex-direction:column; gap:12px;" onsubmit="submitBtn.disabled=true;">
        <div>
          <label for="username">Tên đăng nhập</label>
          <input id="username" name="username" autocomplete="username" type="text" placeholder="admin" required>
        </div>

        <div>
          <label for="password">Mật khẩu</label>
          <input id="password" name="password" autocomplete="current-password" type="password" placeholder="••••••••" required>
        </div>

        <div class="row">
           <div class="muted tiny">Lần thử còn lại: <strong><?= $attempts_left ?></strong></div>
           <button id="submitBtn" class="primary" type="submit">Đăng nhập</button>
         </div>

        <div class="row" style="margin-top:6px;">
          <div class="tiny">Phiên bản: <strong>1.0</strong></div>
          <div class="tiny">Time: <?= date('Y-m-d H:i') ?></div>
        </div>

      
      </form>
    </div>
  </div>

<script>
  // small UX: submit on Enter when focus on password
  const pwd = document.getElementById('password');
  const usr = document.getElementById('username');
  const submitBtn = document.getElementById('submitBtn');

  pwd.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      submitBtn.click();
    }
  });

  // subtle animation when user types wrong (server triggers by re-render with PHP $error)
  if (document.querySelector('.error-box')) {
    // focus and vibration on supported devices
    try { navigator.vibrate && navigator.vibrate(100); } catch(e){}
    const u = document.getElementById('username');
    const p = document.getElementById('password');
    u.classList.add('shake'); p.classList.add('shake');
    setTimeout(()=>{ u.classList.remove('shake'); p.classList.remove('shake'); }, 700);
  }
</script>
</body>
</html>