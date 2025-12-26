<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize Inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    // Address removed from input
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Basic Validation
    if (empty($name) || empty($email) || empty($password)) {
        header("Location: signup.php?error=All fields are required");
        exit;
    }

    if ($password !== $confirm_password) {
        header("Location: signup.php?error=Passwords do not match");
        exit;
    }

    // 3. Check if Email Already Exists
    $checkSql = "SELECT CUSTOMER_ID FROM customer WHERE CUSTOMER_EMAIL = ?";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        header("Location: signup.php?error=Email is already registered");
        exit;
    }

    // 4. Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert New Customer
    // We insert an empty string '' for address since it's required by DB but not provided yet
    $sql = "INSERT INTO customer (CUSTOMER_NAME, CUSTOMER_EMAIL, CUSTOMER_PW, CUSTOMER_TEL, CUSTOMER_ADDRESS) 
            VALUES (?, ?, ?, ?, '')";

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$name, $email, $hashed_password, $phone])) {

        // 6. Login Automatically
        $new_user_id = $pdo->lastInsertId();

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        // Create an active cart for the new user
        $cartSql = "INSERT INTO cart (CUSTOMER_ID, CART_STATUS) VALUES (?, 'active')";
        $cartStmt = $pdo->prepare($cartSql);
        $cartStmt->execute([$new_user_id]);

        // Redirect to Home
        header("Location: index.php");
        exit;
    } else {
        header("Location: signup.php?error=System error. Please try again.");
        exit;
    }
} else {
    header("Location: signup.php");
    exit;
}
