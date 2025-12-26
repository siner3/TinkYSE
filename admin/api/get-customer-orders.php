<?php

/**
 * API Endpoint: Get Orders for a Specific Customer
 * Used by: customers.php modal
 */

require_once '../../config.php'; // Adjust path if needed

// Prevent any output before JSON
ob_clean();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$customer_id = intval($_GET['id']);

try {
    // Fetch orders for this customer
    // We select ORDER_TOTAL explicitly to fix the "undefined" error in JS
    $stmt = $pdo->prepare("
        SELECT 
            ORDER_ID, 
            ORDER_DATE, 
            ORDER_STATUS, 
            ORDER_TOTAL 
        FROM `ORDER` 
        WHERE CUSTOMER_ID = ? 
        ORDER BY ORDER_DATE DESC
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);
} catch (Exception $e) {
    // Return empty array on error so frontend doesn't crash
    echo json_encode([]);
}
