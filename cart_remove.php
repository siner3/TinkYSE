<?php
session_start();
require_once 'config.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. CHECK IF ID IS PROVIDED
if (isset($_GET['id'])) {
    $cartitem_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    try {
        // 3. SECURITY CHECK: GET USER'S ACTIVE CART ID
        // We do this to ensure the user is only deleting items from THEIR own cart
        $stmt = $pdo->prepare("SELECT CART_ID FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS = 'active'");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $cart_id = $cart['CART_ID'];

            // 4. DELETE THE ITEM
            // We verify both the Item ID AND the Cart ID matches
            $delete_stmt = $pdo->prepare("DELETE FROM CARTITEM WHERE CARTITEM_ID = ? AND CART_ID = ?");
            $delete_stmt->execute([$cartitem_id, $cart_id]);
        }
    } catch (PDOException $e) {
        // Optional: Log error
        // error_log($e->getMessage());
    }
}

// 5. REDIRECT BACK TO CART
header("Location: cart.php");
exit;
