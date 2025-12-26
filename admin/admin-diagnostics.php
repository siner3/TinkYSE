<?php

/**
 * LOGIN DIAGNOSTICS SCRIPT
 * Run this to debug login issues
 * 
 * DELETE THIS FILE AFTER USE!
 */

require_once '../config.php';
session_start();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Diagnostics - TINK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8f4ec 0%, #e8dfd3 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #0b2239;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7fb3c8;
            margin-bottom: 30px;
        }

        h2 {
            color: #0b2239;
            font-size: 1.2rem;
            margin-top: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff9f43;
        }

        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .status.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .status.warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .status.info {
            background: #dbeafe;
            color: #0369a1;
            border-left: 4px solid #3b82f6;
        }

        code {
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #0b2239;
        }

        td {
            color: #6b7280;
        }

        .warning-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #92400e;
        }

        .form-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #0b2239;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: #7fb3c8;
            box-shadow: 0 0 0 3px rgba(127, 179, 200, 0.1);
        }

        button {
            background: #ff9f43;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        button:hover {
            background: #ff8c1f;
        }

        .result-box {
            background: #dbeafe;
            border: 1px solid #7fb3c8;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.85rem;
        }

        .test-result {
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
        }

        .test-result.pass {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .test-result.fail {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .delete-warning {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #991b1b;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card">
            <h1>🔍 Login Diagnostics</h1>
            <p class="subtitle">Debug your admin login system</p>

            <div class="delete-warning">
                ⚠️ DELETE THIS FILE AFTER DEBUGGING!
                <br>This file is a security risk and should not be left on your server.
            </div>

            <?php
            // --- TEST 1: Database Connection ---
            ?>
            <h2>Test 1: Database Connection</h2>

            <?php
            try {
                $stmt = $pdo->query("SELECT 1");
                echo '<div class="status success">✅ Database connection successful</div>';
            } catch (Exception $e) {
                echo '<div class="status error">❌ Database connection failed</div>';
                echo '<div class="status error">' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>

            <?php
            // --- TEST 2: ADMIN Table Exists ---
            ?>
            <h2>Test 2: ADMIN Table Exists</h2>

            <?php
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM ADMIN");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'];

                if ($count > 0) {
                    echo '<div class="status success">✅ ADMIN table exists with ' . $count . ' user(s)</div>';
                } else {
                    echo '<div class="status error">❌ ADMIN table exists but is EMPTY</div>';
                    echo '<div class="status warning">⚠️ You need to create at least one admin user</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status error">❌ ADMIN table does not exist or query failed</div>';
                echo '<div class="status error">' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>

            <?php
            // --- TEST 3: List All Admins ---
            ?>
            <h2>Test 3: Current Admin Users</h2>

            <?php
            try {
                $stmt = $pdo->query("SELECT ADMIN_ID, ADMIN_NAME, ADMIN_USERNAME FROM ADMIN");
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($admins) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Username</th></tr>';
                    foreach ($admins as $admin) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($admin['ADMIN_ID']) . '</td>';
                        echo '<td>' . htmlspecialchars($admin['ADMIN_NAME']) . '</td>';
                        echo '<td><code>' . htmlspecialchars($admin['ADMIN_USERNAME']) . '</code></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="status error">❌ No admin users found</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status error">❌ Error fetching admins: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>

            <?php
            // --- TEST 4: Test Login with Form ---
            ?>
            <h2>Test 4: Test Login Credentials</h2>

            <div class="form-box">
                <form method="POST">
                    <div class="form-group">
                        <label for="test-username">Username:</label>
                        <input type="text" id="test-username" name="test_username"
                            placeholder="Enter username (e.g., admin)"
                            value="<?php echo isset($_POST['test_username']) ? htmlspecialchars($_POST['test_username']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="test-password">Password:</label>
                        <input type="password" id="test-password" name="test_password" placeholder="Enter password">
                    </div>

                    <button type="submit">Test Login</button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_username'])) {
                    $test_username = trim($_POST['test_username']);
                    $test_password = trim($_POST['test_password']);

                    echo '<h3 style="margin-top: 20px; color: #0b2239;">Test Results:</h3>';

                    // Step 1: Check if user exists
                    $stmt = $pdo->prepare("SELECT ADMIN_ID, ADMIN_NAME, ADMIN_USERNAME, ADMIN_PASSWORD FROM ADMIN WHERE ADMIN_USERNAME = ?");
                    $stmt->execute([$test_username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($admin) {
                        echo '<div class="test-result pass">✅ User found: ' . htmlspecialchars($admin['ADMIN_NAME']) . '</div>';

                        // Step 2: Check password
                        echo '<div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin: 10px 0;">';
                        echo '<strong>Stored Hash:</strong><br>';
                        echo '<code>' . htmlspecialchars($admin['ADMIN_PASSWORD']) . '</code>';
                        echo '</div>';

                        // Step 3: Verify password
                        if (password_verify($test_password, $admin['ADMIN_PASSWORD'])) {
                            echo '<div class="test-result pass">✅ Password is CORRECT!</div>';
                            echo '<div class="status success">🎉 Login should work. Test it at /admin/login.php</div>';
                        } else {
                            echo '<div class="test-result fail">❌ Password is INCORRECT</div>';
                            echo '<div class="status warning">⚠️ The password you entered does not match the hash in the database</div>';
                        }
                    } else {
                        echo '<div class="test-result fail">❌ User NOT found with username: <code>' . htmlspecialchars($test_username) . '</code></div>';
                        echo '<div class="status warning">⚠️ Check if username is correct and user exists in ADMIN table</div>';
                    }
                }
                ?>
            </div>

            <?php
            // --- TEST 5: Password Hash Generator ---
            ?>
            <h2>Test 5: Generate or Update Password Hash</h2>

            <div class="form-box">
                <form method="POST">
                    <div class="form-group">
                        <label for="new-password">New Password:</label>
                        <input type="password" id="new-password" name="new_password" placeholder="Enter a new password">
                        <small style="color: #7fb3c8; display: block; margin-top: 8px;">
                            Use at least 8 characters with mixed case, numbers, and symbols
                        </small>
                    </div>

                    <input type="hidden" name="action" value="generate_hash">
                    <button type="submit">Generate Hash</button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'generate_hash' && !empty($_POST['new_password'])) {
                    $new_pass = $_POST['new_password'];
                    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 10]);

                    echo '<div style="margin-top: 20px;">';
                    echo '<div class="status info">Generated Hash:</div>';
                    echo '<div class="result-box">' . htmlspecialchars($new_hash) . '</div>';
                    echo '<div class="status info" style="margin-top: 15px;">Use this SQL command to update the password:</div>';
                    echo '<div class="result-box" style="background: #f9fafb; color: #0b2239;">';
                    echo "UPDATE ADMIN SET ADMIN_PASSWORD = '" . $new_hash . "' WHERE ADMIN_USERNAME = 'admin';";
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php
            // --- TEST 6: Session Test ---
            ?>
            <h2>Test 6: Session Configuration</h2>

            <?php
            $session_status = session_status();
            $session_name = session_name();

            if ($session_status === PHP_SESSION_ACTIVE) {
                echo '<div class="status success">✅ Sessions are active</div>';
            } else if ($session_status === PHP_SESSION_NONE) {
                echo '<div class="status warning">⚠️ Sessions not started</div>';
            } else {
                echo '<div class="status error">❌ Session error</div>';
            }

            echo '<table>';
            echo '<tr><th>Setting</th><th>Value</th></tr>';
            echo '<tr><td>Session Name</td><td><code>' . htmlspecialchars($session_name) . '</code></td></tr>';
            echo '<tr><td>Session ID</td><td><code>' . htmlspecialchars(session_id()) . '</code></td></tr>';
            echo '<tr><td>Save Path</td><td><code>' . htmlspecialchars(ini_get('session.save_path')) . '</code></td></tr>';
            echo '<tr><td>Cookie Domain</td><td><code>' . htmlspecialchars(ini_get('session.cookie_domain')) . '</code></td></tr>';
            echo '</table>';
            ?>

            <h2>Quick Fix Checklist</h2>

            <div style="display: grid; gap: 10px;">
                <div class="status info">
                    ✓ Make sure ADMIN table has at least one user
                </div>
                <div class="status info">
                    ✓ Check username is correct (case-sensitive)
                </div>
                <div class="status info">
                    ✓ Use password-generator.php to create a new hash
                </div>
                <div class="status info">
                    ✓ Run UPDATE query to set the new password hash
                </div>
                <div class="status info">
                    ✓ Test login again with the new password
                </div>
            </div>

            <h2>Still Having Issues?</h2>

            <div class="status warning">
                <strong>Common Issues:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Password hash is plain text (not bcrypt) - use password-generator.php</li>
                    <li>Username doesn't exist in database</li>
                    <li>ADMIN table doesn't exist</li>
                    <li>Database connection credentials in config.php are wrong</li>
                    <li>Password hash was copied incorrectly (missing quotes)</li>
                </ul>
            </div>

        </div>
    </div>

</body>

</html>