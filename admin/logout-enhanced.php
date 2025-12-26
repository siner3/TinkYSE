<?php

/**
 * Admin Logout Page (Enhanced Version)
 * 
 * Two modes:
 * 1. Direct logout: /admin/logout.php
 *    - Destroys session
 *    - Clears cookies
 *    - Redirects to login
 * 
 * 2. Logout with confirmation: /admin/logout.php?action=confirm
 *    - Shows confirmation page before logout
 */

session_start();

// Check if user is actually logged in
$is_logged_in = isset($_SESSION['admin_id']);

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'confirm') {
    // Show confirmation page
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Logout - TINK Admin</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
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

            .logout-card {
                background: white;
                padding: 40px;
                border-radius: 16px;
                max-width: 400px;
                width: 100%;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                text-align: center;
                animation: slideUp 0.6s ease;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .logout-icon {
                font-size: 3rem;
                margin-bottom: 20px;
                display: block;
            }

            h1 {
                color: #0b2239;
                margin-bottom: 10px;
                font-size: 1.5rem;
            }

            .logout-subtitle {
                color: #7fb3c8;
                margin-bottom: 30px;
                font-size: 0.95rem;
            }

            .logout-message {
                color: #6b7280;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .button-group {
                display: flex;
                gap: 10px;
            }

            button {
                flex: 1;
                padding: 12px 16px;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                font-size: 0.95rem;
                transition: all 0.3s;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .btn-logout {
                background: #ef4444;
                color: white;
            }

            .btn-logout:hover {
                background: #dc2626;
                transform: translateY(-2px);
            }

            .btn-cancel {
                background: #e5e7eb;
                color: #0b2239;
            }

            .btn-cancel:hover {
                background: #d1d5db;
            }

            .logout-info {
                margin-top: 25px;
                padding-top: 25px;
                border-top: 1px solid #e5e7eb;
                font-size: 0.85rem;
                color: #9ca3af;
            }

            i {
                display: inline-block;
            }
        </style>
    </head>

    <body>
        <div class="logout-card">
            <i class='bx bx-log-out logout-icon' style="color: #ef4444;"></i>

            <h1>Confirm Logout</h1>
            <p class="logout-subtitle">Are you sure?</p>

            <p class="logout-message">
                You will be logged out and redirected to the login page.
            </p>

            <div class="button-group">
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="confirm_logout" value="yes">
                    <button type="submit" class="btn-logout">
                        <i class='bx bx-log-out'></i> Yes, Logout
                    </button>
                </form>
                <button type="button" class="btn-cancel" onclick="history.back()">
                    <i class='bx bx-x'></i> Cancel
                </button>
            </div>

            <div class="logout-info">
                💡 Your session will be cleared from this device
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Handle actual logout (when form is submitted or direct access)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    // User confirmed logout
    doLogout();
} else if (!isset($_GET['action'])) {
    // Direct logout without confirmation
    doLogout();
} else {
    // Unknown action, redirect to login
    header('Location: /admin/login.php');
    exit;
}

/**
 * Performs the actual logout process
 */
function doLogout()
{
    // Clear all session variables
    $_SESSION = [];

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page with logout message
    header('Location: /admin/login.php?logout=success');
    exit;
}
?>