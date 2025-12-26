<?php

/**
 * TINK E-Commerce Jewelry Store - Configuration File
 * Project: Web Application Development (TMF3973)
 */

// =====================================================
// DATABASE CONFIGURATION
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tink_db');
define('DB_PORT', 3306);

// =====================================================
// APPLICATION SETTINGS
// =====================================================

define('APP_NAME', 'TINK');
define('APP_VERSION', '1.0');
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');

// =====================================================
// FILE PATHS
// =====================================================

define('BASE_PATH', dirname(__FILE__));
define('IMAGES_PATH', BASE_PATH . '/public/images/');
define('UPLOADS_PATH', BASE_PATH . '/uploads/');

// =====================================================
// DATABASE CONNECTIONS
// =====================================================

// 1. MySQLi Connection (Keeps your existing code working)
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("MySQLi Error: " . $e->getMessage());
    die("Database connection failed (MySQLi).");
}

// 2. PDO Connection (ADD THIS - Enables the new Dashboard)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    die("Database connection failed (PDO).");
}

// =====================================================
// SESSION & SETTINGS
// =====================================================

date_default_timezone_set(APP_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SESSION_TIMEOUT', 1800);
define('VALIDATE_INPUT', true);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
