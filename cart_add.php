<?php
session_start();
require_once 'config.php';

// 1. CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page with a return URL (optional but good UX)
    header("Location: login.php?error=Please login to shop");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. CHECK REQUEST METHOD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get Data from Form
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $engraving = isset($_POST['engraving_text']) ? trim($_POST['engraving_text']) : null;

    // Basic Validation
    if ($item_id <= 0 || $quantity <= 0) {
        header("Location: catalog.php");
        exit;
    }

    try {
        // 3. GET OR CREATE ACTIVE CART
        // Check if user has an 'active' cart
        $cart_stmt = $pdo->prepare("SELECT CART_ID FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS = 'active'");
        $cart_stmt->execute([$user_id]);
        $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $cart_id = $cart['CART_ID'];
        } else {
            // Create new cart
            $create_cart = $pdo->prepare("INSERT INTO CART (CUSTOMER_ID, CART_STATUS) VALUES (?, 'active')");
            $create_cart->execute([$user_id]);
            $cart_id = $pdo->lastInsertId();
        }

        // 4. FETCH CURRENT ITEM PRICE
        // Always fetch price from DB to prevent tampering from frontend
        $price_stmt = $pdo->prepare("SELECT ITEM_PRICE FROM ITEM WHERE ITEM_ID = ?");
        $price_stmt->execute([$item_id]);
        $item_data = $price_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item_data) {
            die("Item not found.");
        }

        $price = $item_data['ITEM_PRICE'];

        // 5. CHECK IF ITEM ALREADY EXISTS IN CART
        // We check for matching ITEM_ID *AND* matching ENGRAVING.
        // If engraving is different, it counts as a separate row.
        $check_sql = "SELECT CARTITEM_ID, CARTITEM_QUANTITY FROM CARTITEM 
                      WHERE CART_ID = ? AND ITEM_ID = ? AND (CARTITEM_ENGRAVING = ? OR (CARTITEM_ENGRAVING IS NULL AND ? IS NULL))";

        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$cart_id, $item_id, $engraving, $engraving]);
        $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // UPDATE EXISTING QUANTITY
            $new_qty = $existing_item['CARTITEM_QUANTITY'] + $quantity;
            $update_stmt = $pdo->prepare("UPDATE CARTITEM SET CARTITEM_QUANTITY = ? WHERE CARTITEM_ID = ?");
            $update_stmt->execute([$new_qty, $existing_item['CARTITEM_ID']]);
        } else {
            // INSERT NEW LINE ITEM
            $insert_stmt = $pdo->prepare("INSERT INTO CARTITEM (CART_ID, ITEM_ID, CARTITEM_QUANTITY, CARTITEM_PRICE, CARTITEM_ENGRAVING) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->execute([$cart_id, $item_id, $quantity, $price, $engraving]);
        }

        // 6. REDIRECT TO CART
        header("Location: cart.php");
        exit;
    } catch (PDOException $e) {
        // Handle Error
        echo "Error: " . $e->getMessage();
    }
} else {
    // Redirect if accessed directly
    header("Location: catalog.php");
    exit;
}
