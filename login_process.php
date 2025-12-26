<?php
session_start();
require_once 'config.php'; // Ensure you have your database connection here

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get and Sanitize Input
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 2. Check for Empty Fields
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit;
    }

    // 3. Prepare SQL Query (Prevents SQL Injection)
    // We check the 'customer' table based on your SQL file
    $sql = "SELECT * FROM customer WHERE CUSTOMER_EMAIL = ?";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Check if User Exists
        if ($user) {
            // 5. Verify Password
            // Note: Your database uses hashed passwords (e.g. $2y$10$dummy...)
            if (password_verify($password, $user['CUSTOMER_PW'])) {

                // --- LOGIN SUCCESS ---

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set Session Variables
                $_SESSION['user_id'] = $user['CUSTOMER_ID'];
                $_SESSION['user_name'] = $user['CUSTOMER_NAME'];
                $_SESSION['user_email'] = $user['CUSTOMER_EMAIL'];

                // Optional: Check if admin (if you have an admin flag in customer table, or use separate admin login)
                // For now, redirect standard customer to home
                header("Location: index.php");
                exit;
            } else {
                // Wrong Password
                header("Location: login.php?error=Invalid email or password");
                exit;
            }
        } else {
            // User Not Found
            header("Location: login.php?error=Invalid email or password");
            exit;
        }
    } catch (PDOException $e) {
        // Database Error
        error_log("Login Error: " . $e->getMessage()); // Log error internally
        header("Location: login.php?error=System error. Please try again later.");
        exit;
    }
} else {
    // If user tries to access this file directly without submitting form
    header("Location: login.php");
    exit;
}
