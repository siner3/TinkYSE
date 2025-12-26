<?php
// FILE: admin/api/update-stock.php
require_once '../../config.php';
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$item_id = intval($_POST['item_id'] ?? 0);
$stock = intval($_POST['stock'] ?? -1);

if ($item_id <= 0 || $stock < 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE ITEM SET ITEM_STOCK = ? WHERE ITEM_ID = ?");
    $stmt->execute([$stock, $item_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
