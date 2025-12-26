<?php

/**
 * API Endpoint: Get Order Details
 * Path: /admin/api/get-order.php
 * Used by: orders.php modal
 */

require_once '../../config.php';

// Prevent any output before JSON
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Validate request
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$order_id = intval($_GET['id']);

try {
    // 1. Fetch Order Details (DIRECT LINK TO CUSTOMER - thanks to the fix!)
    $stmt = $pdo->prepare("
        SELECT 
            o.ORDER_ID, 
            o.ORDER_DATE, 
            o.ORDER_STATUS, 
            o.ORDER_TOTALAMOUNT, 
            o.CART_ID, 
            c.CUSTOMER_NAME, 
            c.CUSTOMER_EMAIL
        FROM `ORDER` o
        LEFT JOIN CUSTOMER c ON o.CUSTOMER_ID = c.CUSTOMER_ID
        WHERE o.ORDER_ID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // 2. Fetch Items in Cart (Still from CARTITEM, linked via CART_ID)
    $items = [];
    if (!empty($order['CART_ID'])) {
        $stmtItems = $pdo->prepare("
            SELECT 
                ci.CARTITEM_PRICE, 
                ci.CARTITEM_QUANTITY, 
                i.ITEM_NAME, 
                i.ITEM_IMAGE
            FROM CARTITEM ci
            JOIN ITEM i ON ci.ITEM_ID = i.ITEM_ID
            WHERE ci.CART_ID = ?
            ORDER BY ci.ITEM_ID
        ");
        $stmtItems->execute([$order['CART_ID']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }

    // Return JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
    exit;
}
