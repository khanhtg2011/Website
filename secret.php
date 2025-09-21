<?php
session_start();
require __DIR__ . "/config.php";

// Check if user is logged in as admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Check for secret access code (phone number)
$accessGranted = false;
$secretCode = '0799102011';

// Check if code is provided via GET parameter
if (isset($_GET['code']) && $_GET['code'] === $secretCode) {
    $accessGranted = true;
}

// Check if code is provided via POST
if (isset($_POST['secret_code']) && $_POST['secret_code'] === $secretCode) {
    $accessGranted = true;
}

// If not admin and no valid code, show access form
if (!$isAdmin && !$accessGranted) {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Secret Access</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .access-form {
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.2);
        }

        input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: white;
        }

        .hint {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 15px;
            font-style: italic;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Secret Access</h1>
        <div class="subtitle">Enter the secret code to unlock the hidden page</div>

        <form method="post" class="access-form">
            <div class="input-group">
                <label for="secret_code">Secret Code:</label>
                <input type="password" id="secret_code" name="secret_code" placeholder="Enter secret code..." required>
            </div>
            <button type="submit" class="btn">Unlock Secret</button>
        </form>

        <div class="hint">
            💡 Hint: The secret code is something very personal to the admin...
        </div>

        <a href="/" class="back-link">← Back to Gallery</a>
    </div>
</body>
</html>
<?php
    exit;
}

// If we reach here, access is granted - show the secret page
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 Secret Page Unlocked!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
        }

        .hero {
            text-align: center;
            padding: 80px 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .stat-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-description {
            opacity: 0.8;
            line-height: 1.6;
        }

        .secret-message {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 60px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .secret-message h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #ffd700;
        }

        .secret-message p {
            font-size: 1.1rem;
            line-height: 1.8;
            opacity: 0.9;
        }

        .actions {
            text-align: center;
            margin-bottom: 60px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn.secondary {
            background: linear-gradient(135deg, #4caf50, #45a049);
        }

        .btn.secondary:hover {
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .footer p {
            opacity: 0.7;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stat-card {
                padding: 25px;
            }

            .secret-message {
                padding: 30px 20px;
            }

            .secret-message h2 {
                font-size: 1.5rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .secret-message {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        /* Text Editor Styles */
        .editor-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .editor-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .editor-header h2 {
            margin: 0;
            font-size: 1.8rem;
            color: #ffd700;
        }

        .editor-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.8;
        }

        .editor-toolbar {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .editor-toolbar button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            min-width: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .editor-toolbar button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .editor-toolbar button.danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .editor-toolbar button.danger:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .separator {
            width: 1px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 5px;
        }

        .editor-main {
            padding: 30px;
        }

        #textEditor {
            width: 100%;
            min-height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            padding: 20px;
            resize: vertical;
            outline: none;
            transition: all 0.3s ease;
        }

        #textEditor:focus {
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        }

        #textEditor::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .editor-footer {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .file-info {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.8;
        }

        .editor-actions {
            display: flex;
            gap: 10px;
        }

        .editor-actions .btn {
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 20px;
        }

        @media (max-width: 768px) {
            .editor-header {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .editor-header h2 {
                font-size: 1.5rem;
            }

            .editor-stats {
                width: 100%;
                justify-content: space-between;
            }

            .editor-toolbar {
                padding: 10px 20px;
                gap: 8px;
            }

            .editor-toolbar button {
                padding: 6px 10px;
                min-width: 30px;
                font-size: 12px;
            }

            .editor-main {
                padding: 20px;
            }

            #textEditor {
                min-height: 300px;
                font-size: 14px;
                padding: 15px;
            }

            .editor-footer {
                padding: 15px 20px;
                flex-direction: column;
                align-items: stretch;
            }

            .file-info {
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .editor-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>🎉 Secret Page Unlocked!</h1>
        <p>Welcome to the hidden realm! You've successfully entered the secret code and gained access to this exclusive page.</p>
    </div>

    <div class="content">
        <div class="editor-container">
            <div class="editor-header">
                <h2>📝 Secret Text Editor</h2>
                <div class="editor-stats">
                    <span id="wordCount">0 words</span>
                    <span id="charCount">0 characters</span>
                    <span id="autoSaveStatus">Auto-saved</span>
                </div>
            </div>

            <div class="editor-toolbar">
                <button onclick="formatText('bold')" title="Bold (Ctrl+B)">
                    <strong>B</strong>
                </button>
                <button onclick="formatText('italic')" title="Italic (Ctrl+I)">
                    <em>I</em>
                </button>
                <button onclick="formatText('underline')" title="Underline (Ctrl+U)">
                    <u>U</u>
                </button>
                <span class="separator"></span>
                <button onclick="insertText('📝 ')" title="Note">📝</button>
                <button onclick="insertText('✅ ')" title="Check">✅</button>
                <button onclick="insertText('❌ ')" title="Cross">❌</button>
                <button onclick="insertText('⭐ ')" title="Star">⭐</button>
                <span class="separator"></span>
                <button onclick="clearEditor()" title="Clear All" class="danger">🗑️ Clear</button>
                <button onclick="downloadText()" title="Download">💾 Download</button>
            </div>

            <div class="editor-main">
                <textarea id="textEditor" placeholder="Start writing your secret thoughts here...

💡 Tips:
• Your text is automatically saved every 30 seconds
• Use Ctrl+S to save manually
• Download your work anytime
• This is your private space - write anything you want!

Happy writing! ✍️"></textarea>
            </div>

            <div class="editor-footer">
                <div class="file-info">
                    <span id="lastSaved">Never saved</span>
                    <span id="fileSize">0 KB</span>
                </div>
                <div class="editor-actions">
                    <button onclick="saveText()" class="btn primary">💾 Save</button>
                    <button onclick="loadText()" class="btn secondary">📂 Load</button>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="/" class="btn">← Back to Gallery</a>
            <a href="/admin_logout" class="btn secondary">🚪 Logout</a>
        </div>
    </div>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> Khanh's Photo Gallery - Secret Page Access Granted</p>
    </div>

    <script>
        // Text Editor Functionality
        let autoSaveTimer;
        let lastSavedContent = '';

        document.addEventListener('DOMContentLoaded', function() {
            const textEditor = document.getElementById('textEditor');

            // Load saved content
            loadSavedContent();

            // Initialize counters
            updateCounters();

            // Set up event listeners
            textEditor.addEventListener('input', function() {
                updateCounters();
                scheduleAutoSave();
            });

            textEditor.addEventListener('keydown', function(e) {
                // Ctrl+S to save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    saveText();
                }
                // Ctrl+B for bold
                else if (e.ctrlKey && e.key === 'b') {
                    e.preventDefault();
                    formatText('bold');
                }
                // Ctrl+I for italic
                else if (e.ctrlKey && e.key === 'i') {
                    e.preventDefault();
                    formatText('italic');
                }
                // Ctrl+U for underline
                else if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                    formatText('underline');
                }
            });

            // Add click effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.3)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.left = (e.offsetX - 10) + 'px';
                    ripple.style.top = (e.offsetY - 10) + 'px';
                    ripple.style.width = '20px';
                    ripple.style.height = '20px';

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        function updateCounters() {
            const text = document.getElementById('textEditor').value;
            const words = text.trim() ? text.trim().split(/\s+/).length : 0;
            const chars = text.length;

            document.getElementById('wordCount').textContent = words + ' words';
            document.getElementById('charCount').textContent = chars + ' characters';
            document.getElementById('fileSize').textContent = (new Blob([text]).size / 1024).toFixed(1) + ' KB';
        }

        function formatText(command) {
            const textarea = document.getElementById('textEditor');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);

            let replacement = '';
            switch(command) {
                case 'bold':
                    replacement = `**${selectedText}**`;
                    break;
                case 'italic':
                    replacement = `*${selectedText}*`;
                    break;
                case 'underline':
                    replacement = `__${selectedText}__`;
                    break;
            }

            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + replacement.length, start + replacement.length);
            updateCounters();
            scheduleAutoSave();
        }

        function insertText(text) {
            const textarea = document.getElementById('textEditor');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;

            textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + text.length, start + text.length);
            updateCounters();
            scheduleAutoSave();
        }

        function clearEditor() {
            if (confirm('Are you sure you want to clear all text? This cannot be undone.')) {
                document.getElementById('textEditor').value = '';
                updateCounters();
                scheduleAutoSave();
            }
        }

        function saveText() {
            const content = document.getElementById('textEditor').value;
            const timestamp = new Date().toLocaleString();

            try {
                localStorage.setItem('secretEditorContent', content);
                localStorage.setItem('secretEditorTimestamp', timestamp);
                lastSavedContent = content;

                document.getElementById('lastSaved').textContent = 'Saved at ' + timestamp;
                document.getElementById('autoSaveStatus').textContent = 'Saved manually';
                document.getElementById('autoSaveStatus').style.color = '#4caf50';

                setTimeout(() => {
                    document.getElementById('autoSaveStatus').style.color = '';
                    document.getElementById('autoSaveStatus').textContent = 'Auto-saved';
                }, 2000);

                console.log('Text saved successfully');
            } catch (e) {
                alert('Failed to save: ' + e.message);
            }
        }

        function loadText() {
            const savedContent = localStorage.getItem('secretEditorContent');
            const savedTimestamp = localStorage.getItem('secretEditorTimestamp');

            if (savedContent) {
                if (confirm('Load previously saved content? This will replace your current text.')) {
                    document.getElementById('textEditor').value = savedContent;
                    document.getElementById('lastSaved').textContent = 'Loaded from ' + (savedTimestamp || 'unknown time');
                    updateCounters();
                    lastSavedContent = savedContent;
                }
            } else {
                alert('No saved content found.');
            }
        }

        function loadSavedContent() {
            const savedContent = localStorage.getItem('secretEditorContent');
            const savedTimestamp = localStorage.getItem('secretEditorTimestamp');

            if (savedContent) {
                document.getElementById('textEditor').value = savedContent;
                document.getElementById('lastSaved').textContent = 'Loaded from ' + (savedTimestamp || 'unknown time');
                lastSavedContent = savedContent;
            }
        }

        function downloadText() {
            const content = document.getElementById('textEditor').value;
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');

            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = `secret-notes-${timestamp}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            console.log('Text downloaded as file');
        }

        function scheduleAutoSave() {
            clearTimeout(autoSaveTimer);
            document.getElementById('autoSaveStatus').textContent = 'Unsaved changes';

            autoSaveTimer = setTimeout(() => {
                const currentContent = document.getElementById('textEditor').value;
                if (currentContent !== lastSavedContent) {
                    saveText();
                }
            }, 30000); // Auto-save after 30 seconds
        }

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            .btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php
// Log the secret access for security
$logMessage = "[" . date('Y-m-d H:i:s') . "] Secret page accessed";
if ($isAdmin) {
    $logMessage .= " by admin user";
} else {
    $logMessage .= " via secret code";
}
error_log($logMessage . "\n", 3, __DIR__ . "/secret_access.log");
?>