<?php

/**
 * Admin Logout Page
 * Destroys the session and redirects to login page
 * 
 * Access: /admin/logout.php
 */

session_start();

// Destroy all session data
session_destroy();

// Clear any cookies set during login
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: /admin/login.php');
exit;
