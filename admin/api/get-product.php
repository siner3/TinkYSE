<?php
require_once '../../config.php'; // Adjust path to config.php as needed

header('Content-Type: application/json');

$item_id = intval($_GET['item_id'] ?? 0);

if (!$item_id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// 1. Get the requested item first to check its links
$stmt = $pdo->prepare("SELECT * FROM ITEM WHERE ITEM_ID = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

// 2. Determine the Group ID
// If it has a PARENT_ID, that's the group. If not, use its own ID (for single items).
$group_key = $item['PARENT_ID'] ?? $item['ITEM_ID'];

// 3. Fetch ALL items in this group
// We check if an item IS the group key OR belongs TO the group key
$stmt = $pdo->prepare("
    SELECT * FROM ITEM 
    WHERE ITEM_ID = ? 
    OR PARENT_ID = ? 
    OR (PARENT_ID IS NULL AND ITEM_ID = ?)
    ORDER BY ITEM_ID ASC
");
// We pass the key 3 times to cover all logic cases
$stmt->execute([$group_key, $group_key, $group_key]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return array of items (even if it's just 1)
echo json_encode($variants);
