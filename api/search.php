<?php
require_once '../config.php'; // Adjust this path if your config is elsewhere

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Only search if 2 or more characters
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search in Name, Description, or Tags
    $sql = "SELECT ITEM_ID, ITEM_NAME, ITEM_IMAGE, ITEM_PRICE, ITEM_CATEGORY 
            FROM ITEM 
            WHERE (ITEM_NAME LIKE ? OR ITEM_TAGS LIKE ? OR ITEM_CATEGORY LIKE ?)
            AND ITEM_ACTIVE = 1
            LIMIT 5"; // Limit results to keep it fast

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
