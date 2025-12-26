<?php
session_start();
require_once 'config.php';

// 1. GET DESIGNER ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: designers.php");
    exit;
}

$designer_id = $_GET['id'];

// 2. FETCH DESIGNER DETAILS
$d_stmt = $pdo->prepare("SELECT * FROM DESIGNER WHERE DESIGNER_ID = ?");
$d_stmt->execute([$designer_id]);
$designer_info = $d_stmt->fetch(PDO::FETCH_ASSOC);

if (!$designer_info) {
    echo "Designer not found.";
    exit;
}

// 3. FETCH ITEMS FOR THIS DESIGNER
$sql = "SELECT * FROM ITEM WHERE DESIGNER_ID = ? AND ITEM_ACTIVE = 1 ORDER BY ITEM_ID DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$designer_id]);
$raw_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. GROUP VARIANTS
$grouped_products = [];
foreach ($raw_items as $item) {
    $key = $item['PARENT_ID'] ? $item['PARENT_ID'] : $item['ITEM_ID'];
    if (!isset($grouped_products[$key])) {
        $grouped_products[$key] = ['base' => $item, 'variants' => []];
    }
    $grouped_products[$key]['variants'][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($designer_info['DESIGNER_NAME']) ?> | TINK</title>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="page-title">
        <div
            style="font-size: 0.9rem; letter-spacing: 2px; color: #888; margin-bottom: 10px; text-transform: uppercase;">
            Designer Collection
        </div>
        <h1><?= htmlspecialchars($designer_info['DESIGNER_NAME']) ?></h1>
    </div>

    <div style="max-width: 1400px; margin: 0 auto; padding: 0 40px 100px;">

        <div style="margin-bottom: 30px;">
            <a href="designers.php" style="font-size: 0.9rem; color: #666; text-decoration: underline;">
                <i class="fa-solid fa-arrow-left"></i> Back to Designers
            </a>
        </div>

        <main class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php if (empty($grouped_products)): ?>
                <div style="grid-column:1/-1; text-align:center; padding:60px; color:#888;">
                    <i class="fa-regular fa-folder-open" style="font-size:2.5rem; margin-bottom:15px;"></i>
                    <p>No products found for this designer yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_products as $group):
                    $base = $group['base'];
                    $variants = $group['variants'];
                ?>
                    <div class="product-card">
                        <div class="image-wrapper">
                            <a href="product_detail.php?id=<?= $base['ITEM_ID'] ?>">
                                <img src="<?= htmlspecialchars($base['ITEM_IMAGE']) ?>" id="img-<?= $base['ITEM_ID'] ?>"
                                    alt="<?= htmlspecialchars($base['ITEM_NAME']) ?>">
                            </a>

                            <?php if (count($variants) > 1): ?>
                                <div class="swatches">
                                    <?php foreach ($variants as $v):
                                        $mat = strtolower($v['ITEM_MATERIAL']);
                                        $cls = 'silver';
                                        if (strpos($mat, 'gold') !== false) $cls = 'gold';
                                        if (strpos($mat, 'rose') !== false) $cls = 'rose';
                                    ?>
                                        <span class="swatch <?= $cls ?>" onmouseover="updateCard(this, '<?= $base['ITEM_ID'] ?>')"
                                            data-image="<?= htmlspecialchars($v['ITEM_IMAGE']) ?>"
                                            data-price="RM <?= number_format($v['ITEM_PRICE'], 2) ?>"
                                            data-name="<?= htmlspecialchars($v['ITEM_NAME']) ?>">
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="product-name" id="title-<?= $base['ITEM_ID'] ?>">
                            <?= htmlspecialchars($base['ITEM_NAME']) ?>
                        </div>

                        <div class="price" id="price-<?= $base['ITEM_ID'] ?>">
                            RM <?= number_format($base['ITEM_PRICE'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <footer class="site-footer">
        <div style="text-align:center; margin-top:40px; font-size:0.8rem; color:#888;">&copy; 2025 Tink.</div>
    </footer>

    <script>
        function updateCard(el, id) {
            const img = document.getElementById('img-' + id);
            const price = document.getElementById('price-' + id);
            const title = document.getElementById('title-' + id);

            if (img && el.dataset.image) img.src = el.dataset.image;
            if (price && el.dataset.price) price.innerText = el.dataset.price;
            if (title && el.dataset.name) title.innerText = el.dataset.name;
        }
    </script>

</body>

</html>