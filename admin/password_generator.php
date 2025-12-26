<?php

/**
 * ADMIN PASSWORD GENERATOR & MANAGER
 * Use this script to generate bcrypt hashes for admin passwords
 * 
 * Usage:
 * 1. Set $password variable below
 * 2. Run this script in browser or CLI
 * 3. Copy the hash into your ADMIN table
 * 
 * DELETE THIS FILE AFTER USE FOR SECURITY!
 */

// Only allow if password hash is being generated
$action = $_GET['action'] ?? 'generate';
$password = $_POST['password'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Generator - TINK</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f4ec 0%, #e8dfd3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #0b2239;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .subtitle {
            color: #7fb3c8;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #0b2239;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            outline: none;
            border-color: #7fb3c8;
            box-shadow: 0 0 0 3px rgba(127, 179, 200, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #ff9f43;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        button:hover {
            background: #ff8c1f;
            transform: translateY(-2px);
        }

        .result-box {
            background: #f0f9ff;
            border: 1px solid #7fb3c8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .result-label {
            color: #0b2239;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .result-value {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            word-break: break-all;
            color: #0b2239;
            margin-bottom: 15px;
        }

        .copy-btn {
            background: #7fb3c8;
            font-size: 0.85rem;
            padding: 8px 12px;
        }

        .copy-btn:hover {
            background: #5a8ba3;
        }

        .copy-btn.copied {
            background: #10b981;
        }

        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .warning strong {
            display: block;
            margin-bottom: 5px;
        }

        .instructions {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .instructions ol {
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }

        .success {
            color: #059669;
            font-weight: 600;
            margin-top: 10px;
        }

        .hint {
            color: #7fb3c8;
            font-size: 0.85rem;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>🔐 Password Generator</h1>
        <p class="subtitle">Generate bcrypt hash for admin accounts</p>

        <div class="warning">
            <strong>⚠️ Security Warning:</strong>
            Delete this file after use. Never leave it on your server!
        </div>

        <div class="instructions">
            <strong>How to use:</strong>
            <ol>
                <li>Enter a password below</li>
                <li>Click "Generate Hash"</li>
                <li>Copy the hash</li>
                <li>Update your ADMIN table: <code>UPDATE ADMIN SET ADMIN_PASSWORD = '[HASH]'</code></li>
                <li>Delete this file</li>
            </ol>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter a strong password" required
                    minlength="8">
                <div class="hint">
                    💡 Use at least 8 characters with uppercase, numbers, and symbols
                </div>
            </div>

            <button type="submit">Generate Hash</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($password)): ?>
            <?php
            // Validate password strength
            $hasUppercase = preg_match('/[A-Z]/', $password);
            $hasLowercase = preg_match('/[a-z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[!@#$%^&*]/', $password);
            $isLongEnough = strlen($password) >= 8;

            $isStrong = $hasUppercase && $hasLowercase && $hasNumber && $isLongEnough;

            // Generate hash
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            ?>

            <div class="result-box">
                <div class="result-label">Generated Hash:</div>
                <div class="result-value" id="hashValue"><?php echo $hash; ?></div>

                <button type="button" class="copy-btn" onclick="copyHash()">
                    📋 Copy Hash to Clipboard
                </button>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">
                    <div class="result-label" style="margin-bottom: 15px;">Password Strength:</div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $hasUppercase ? '✅' : '❌'; ?></span>
                            <span>Uppercase letter (A-Z)</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $hasLowercase ? '✅' : '❌'; ?></span>
                            <span>Lowercase letter (a-z)</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $hasNumber ? '✅' : '❌'; ?></span>
                            <span>Number (0-9)</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $hasSpecial ? '✅' : '❌'; ?></span>
                            <span>Special character (!@#$%^&*)</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $isLongEnough ? '✅' : '❌'; ?></span>
                            <span>At least 8 characters</span>
                        </div>
                    </div>

                    <?php if ($isStrong): ?>
                        <div class="success">✅ Password is strong and secure!</div>
                    <?php else: ?>
                        <div style="color: #f59e0b; margin-top: 10px;">
                            ⚠️ Password could be stronger. Consider adding more variety.
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">
                    <div class="result-label">SQL Update Command:</div>
                    <div class="result-value">
                        UPDATE ADMIN SET ADMIN_PASSWORD = '<?php echo $hash; ?>' WHERE ADMIN_USERNAME = 'admin';
                    </div>
                    <button type="button" class="copy-btn" onclick="copySql()">
                        📋 Copy SQL Command
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyHash() {
            const hashValue = document.getElementById('hashValue').textContent;
            copyToClipboard(hashValue, event.target);
        }

        function copySql() {
            const sqlCommand = document.querySelector('.result-value:last-of-type').textContent;
            copyToClipboard(sqlCommand, event.target);
        }

        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.classList.add('copied');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(() => {
                alert('Failed to copy. Please copy manually.');
            });
        }
    </script>

</body>

</html>