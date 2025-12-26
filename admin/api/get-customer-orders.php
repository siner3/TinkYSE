<?php
// FILE: admin/api/get-customer-orders.php
require_once '../../config.php';

// Clean output buffer to ensure valid JSON
ob_clean();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No Customer ID provided']);
    exit;
}

$customer_id = intval($_GET['id']);

try {
    // Select orders for this customer using backticks for the table name `ORDER`
    $stmt = $pdo->prepare("
        SELECT ORDER_ID, ORDER_DATE, ORDER_STATUS, ORDER_TOTALAMOUNT 
        FROM `ORDER` 
        WHERE CUSTOMER_ID = ? 
        ORDER BY ORDER_DATE DESC
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
