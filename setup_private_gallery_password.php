<?php
// Standalone setup script - doesn't require database

// Check if user is admin
session_start();
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
if (!$isAdmin) {
    header("Location: /");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($password)) {
        $error = "Password cannot be empty";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        // Store password as plain text
        $plainPassword = $password;

        // Store in a config file
        $configFile = __DIR__ . '/private_gallery_config.php';
        $config = "<?php\n";
        $config .= "// Private Gallery Password Configuration\n";
        $config .= "// Generated on " . date('Y-m-d H:i:s') . "\n";
        $config .= "define('PRIVATE_GALLERY_PASSWORD', '" . addslashes($plainPassword) . "');\n";
        $config .= "define('PRIVATE_GALLERY_ENABLED', true);\n";

        if (file_put_contents($configFile, $config)) {
            $message = "Private gallery password set successfully! You can now access it from the main gallery.";
        } else {
            $error = "Failed to save configuration. Check file permissions.";
        }
    }
}

// Check if already configured
$configExists = file_exists(__DIR__ . '/private_gallery_config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Private Gallery Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #dc3545;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background: #c82333;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        a {
            color: #dc3545;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Setup Private Gallery Password</h1>

        <?php if ($message): ?>
            <div class="message success">
                ✅ <?php echo htmlspecialchars($message); ?>
                <br><br>
                <a href="/">← Back to Gallery</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($configExists && !$message): ?>
            <div class="message info">
                ℹ️ Private gallery password is already configured. You can update it below.
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="password">Private Gallery Password:</label>
                <input type="password" id="password" name="password" required
                       placeholder="Enter a secure password (min 6 characters)"
                       minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Re-enter the same password">
            </div>

            <button type="submit">Set Private Gallery Password</button>
        </form>

        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            This password will be required to access your private memories from the main gallery.<br>
            It is separate from your admin login password.
        </div>
    </div>
</body>
</html>