<?php
session_start();
require_once 'config.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // 2. Find the Active Cart
        $stmt = $pdo->prepare("SELECT CART_ID FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS = 'active'");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $cart_id = $cart['CART_ID'];

            // 3. Update Cart Status to 'completed' (This registers it as an order)
            // We also assume you might want to save the final total here if your DB has a column for it, otherwise just status.
            $update_stmt = $pdo->prepare("UPDATE CART SET CART_STATUS = 'completed' WHERE CART_ID = ?");
            $update_stmt->execute([$cart_id]);

            // 4. Create a NEW Active Cart for the user (so they can shop again)
            $new_cart_stmt = $pdo->prepare("INSERT INTO CART (CUSTOMER_ID, CART_STATUS) VALUES (?, 'active')");
            $new_cart_stmt->execute([$user_id]);

            // 5. Redirect to Thank You Page
            header("Location: thankyou.php?order_id=" . $cart_id);
            exit;
        } else {
            // Cart was empty or not found
            header("Location: cart.php?error=empty");
            exit;
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: cart.php");
    exit;
}
