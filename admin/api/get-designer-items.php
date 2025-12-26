<?php
// FILE: admin/api/get-designer-items.php
require_once '../../config.php';
ob_clean();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$designer_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT ITEM_ID, ITEM_NAME, ITEM_CATEGORY, ITEM_PRICE, ITEM_STOCK, ITEM_IMAGE
        FROM ITEM
        WHERE DESIGNER_ID = ?
        ORDER BY ITEM_NAME ASC
    ");
    $stmt->execute([$designer_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($items);
} catch (Exception $e) {
    echo json_encode([]);
}
