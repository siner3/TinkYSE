<?php
session_start();
require_once 'config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Start Transaction to ensure data integrity
    $pdo->beginTransaction();

    // --- STEP 1: FETCH ACTIVE CART ---
    $stmt = $pdo->prepare("SELECT CART_ID FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS = 'active'");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception("No active shopping bag found.");
    }
    $cart_id = $cart['CART_ID'];

    // --- STEP 2: CALCULATE TOTAL (Server-Side Calculation) ---
    // We do not trust $_POST for prices. We recalculate from DB.

    // Fetch all items in this cart
    $sql = "SELECT ci.CARTITEM_ID, ci.CARTITEM_QUANTITY, ci.CARTITEM_PRICE
            FROM CARTITEM ci
            WHERE ci.CART_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;

    foreach ($cart_items as $item) {
        // Calculate base item price
        $item_total = $item['CARTITEM_PRICE'];

        // Add Charms Price for this specific item
        $charm_stmt = $pdo->prepare("SELECT SUM(c.CHARM_PRICE) as charm_total 
                                     FROM CARTITEM_CHARM cc 
                                     JOIN CHARM c ON cc.CHARM_ID = c.CHARM_ID 
                                     WHERE cc.CARTITEM_ID = ?");
        $charm_stmt->execute([$item['CARTITEM_ID']]);
        $charm_data = $charm_stmt->fetch(PDO::FETCH_ASSOC);
        $charms_price = $charm_data['charm_total'] ?? 0;

        // Add to subtotal: (Item Price + Charms) * Quantity
        $subtotal += ($item_total + $charms_price) * $item['CARTITEM_QUANTITY'];
    }

    // Shipping Logic (Match your cart.php logic)
    $shipping_cost = 7.00; // Standard shipping
    $final_total = $subtotal + $shipping_cost;

    // --- STEP 3: UPDATE CART STATUS ---
    $stmt = $pdo->prepare("UPDATE CART SET CART_STATUS = 'completed' WHERE CART_ID = ?");
    $stmt->execute([$cart_id]);

    // --- STEP 4: CREATE ORDER RECORD ---
    // This is the step that was missing before!
    $stmt = $pdo->prepare("INSERT INTO `order` (CUSTOMER_ID, CART_ID, ORDER_DATE, ORDER_STATUS, ORDER_TOTAL) 
                           VALUES (?, ?, NOW(), 'completed', ?)");
    $stmt->execute([$user_id, $cart_id, $final_total]);
    $new_order_id = $pdo->lastInsertId();

    // --- STEP 5: CREATE PAYMENT RECORD ---
    $stmt = $pdo->prepare("INSERT INTO payment (ORDER_ID, PAYMENT_DATE, PAYMENT_METHOD, PAYMENT_STATUS, PAYMENT_AMOUNT) 
                           VALUES (?, NOW(), 'Credit Card', 'completed', ?)");
    $stmt->execute([$new_order_id, $final_total]);

    // --- STEP 6: CREATE NEW CART FOR USER ---
    $stmt = $pdo->prepare("INSERT INTO CART (CUSTOMER_ID, CART_STATUS) VALUES (?, 'active')");
    $stmt->execute([$user_id]);

    // Commit all changes
    $pdo->commit();

    // Success! Redirect to account page
    header("Location: account.php?status=success");
    exit;
} catch (Exception $e) {
    // If anything goes wrong, undo all database changes
    $pdo->rollBack();
    die("Checkout Error: " . $e->getMessage());
}
