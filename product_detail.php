<?php
session_start();
require_once 'config.php';

// 1. GET PRODUCT ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: catalog.php");
    exit;
}
$item_id = intval($_GET['id']);

// 2. FETCH PRODUCT DETAILS & DESIGNER
$sql = "SELECT i.*, d.DESIGNER_NAME 
        FROM ITEM i 
        LEFT JOIN DESIGNER d ON i.DESIGNER_ID = d.DESIGNER_ID 
        WHERE i.ITEM_ID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$item_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

// 3. FETCH GALLERY IMAGES (FIXED LOGIC)
$gallery_images = [];

// Step A: Try to get gallery from the current item itself
if (!empty($product['GALLERY_IMAGES'])) {
    $decoded = json_decode($product['GALLERY_IMAGES'], true);
    if (is_array($decoded)) {
        $gallery_images = $decoded;
    }
}

// Step B: If current item has no gallery, check if it belongs to a group and try to fetch from a sibling
if (empty($gallery_images) && !empty($product['PARENT_ID'])) {
    // Find any item in the same group that HAS gallery images
    $group_sql = "SELECT GALLERY_IMAGES FROM ITEM 
                  WHERE PARENT_ID = ? AND GALLERY_IMAGES IS NOT NULL AND GALLERY_IMAGES != '[]' 
                  LIMIT 1";
    $g_stmt = $pdo->prepare($group_sql);
    $g_stmt->execute([$product['PARENT_ID']]);
    $group_data = $g_stmt->fetch(PDO::FETCH_ASSOC);

    if ($group_data && !empty($group_data['GALLERY_IMAGES'])) {
        $decoded = json_decode($group_data['GALLERY_IMAGES'], true);
        if (is_array($decoded)) {
            $gallery_images = $decoded;
        }
    }
}

// Step C: Ensure current variant's main image is FIRST in the list
$current_main_img = $product['ITEM_IMAGE'];
if (!empty($current_main_img)) {
    // Remove if duplicate to avoid showing twice
    $key = array_search($current_main_img, $gallery_images);
    if ($key !== false) {
        unset($gallery_images[$key]);
    }
    // Add to front of array
    array_unshift($gallery_images, $current_main_img);
}

// Fallback if completely empty
if (empty($gallery_images)) {
    $gallery_images[] = 'assets/images/placeholder.png'; // Ensure this path is valid
}

// 4. FETCH VARIANTS (Siblings in the same group)
$variants = [];
if (!empty($product['PARENT_ID'])) {
    // Fetch all items sharing the same PARENT_ID
    $var_sql = "SELECT ITEM_ID, ITEM_COLOR, ITEM_MATERIAL, ITEM_IMAGE 
                FROM ITEM 
                WHERE PARENT_ID = ? AND ITEM_ACTIVE = 1 
                ORDER BY ITEM_ID ASC";
    $v_stmt = $pdo->prepare($var_sql);
    $v_stmt->execute([$product['PARENT_ID']]);
    $variants = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // If no parent ID, it's a standalone item (just itself)
    $variants[] = [
        'ITEM_ID' => $product['ITEM_ID'],
        'ITEM_COLOR' => $product['ITEM_COLOR'],
        'ITEM_MATERIAL' => $product['ITEM_MATERIAL'],
        'ITEM_IMAGE' => $product['ITEM_IMAGE']
    ];
}

// 5. FETCH REVIEWS
$rev_sql = "SELECT r.*, c.CUSTOMER_NAME 
            FROM REVIEW r 
            JOIN CUSTOMER c ON r.CUSTOMER_ID = c.CUSTOMER_ID 
            WHERE r.ITEM_ID = ? AND r.REVIEW_ACTIVE = 1 
            ORDER BY r.REVIEW_DATE DESC";
$r_stmt = $pdo->prepare($rev_sql);
$r_stmt->execute([$item_id]);
$reviews = $r_stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. FETCH "MORE FROM DESIGNER"
$more_sql = "SELECT ITEM_ID, ITEM_NAME, ITEM_PRICE, ITEM_IMAGE 
             FROM ITEM 
             WHERE DESIGNER_ID = ? AND ITEM_ID != ? AND ITEM_ACTIVE = 1 
             LIMIT 4";
$m_stmt = $pdo->prepare($more_sql);
$m_stmt->execute([$product['DESIGNER_ID'], $item_id]);
$related_items = $m_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ITEM_NAME']) ?> | Tuk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/product_detail.css">
    <style>
    /* --- GLOBAL --- */

    /* --- HEADER (Compact) --- */
    .site-header {
        background-color: #203742;
        color: #fff;
        padding: 15px 0;
    }

    .header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .main-nav {
        display: flex;
        gap: 25px;
    }

    .nav-link {
        color: #ccc;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
    }

    .nav-link:hover,
    .nav-link.active {
        color: #fff;
    }

    .header-right {
        display: flex;
        gap: 20px;
        color: white;
    }

    /* --- PAGE TITLE --- */
    .page-title {
        text-align: center;
        padding: 50px 0 30px;
        margin-top: 60px;
    }

    .page-title h1 {
        font-family: var(--font-serif);
        font-size: 3rem;
        color: var(--text-slate);
        font-weight: 400;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: #f8f4ec;
        color: #1c1c1c;
    }

    h1,
    h2,
    h3,
    h4 {
        font-family: 'Playfair Display', serif;
        font-weight: 400;
    }

    a {
        text-decoration: none;
        color: inherit;
        transition: 0.3s;
    }



    /* BREADCRUMBS */
    .breadcrumbs {
        padding: 0 60px;
        font-size: 12px;
        color: #888;
        padding-bottom: 20px;
        margin-top: 120px;
    }

    .breadcrumbs a:hover {
        color: #000;
    }

    /* PRODUCT LAYOUT */
    .product-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        padding: 20px 60px 80px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* GALLERY */
    .gallery-wrapper {
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 15px;
        height: 600px;
    }

    .thumbnails {
        display: flex;
        flex-direction: column;
        gap: 15px;
        overflow-y: auto;
    }

    .thumbnails img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        cursor: pointer;
        border: 1px solid transparent;
        transition: 0.2s;
    }

    .thumbnails img:hover,
    .thumbnails img.active {
        border-color: #333;
    }

    .main-image {
        width: 100%;
        height: 100%;
        background: #fff;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* INFO */
    .product-info {
        padding-top: 10px;
    }

    .product-title {
        font-size: 32px;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .designer-name {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }

    .product-price {
        font-size: 24px;
        font-weight: 500;
        margin-bottom: 25px;
    }

    /* VARIANTS */
    .variants-section {
        margin-bottom: 20px;
    }

    .swatches {
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }

    .swatch {
        width: 30px;
        height: 30px;
        border-radius: 4px;
        border: 1px solid #ddd;
        cursor: pointer;
        position: relative;
        background-size: cover;
    }

    .swatch.active {
        border: 1px solid #000;
        outline: 1px solid #000;
    }

    /* Helper Color Classes */
    .bg-silver {
        background-color: #C0C0C0;
    }

    .bg-gold {
        background-color: #D4AF37;
    }

    .bg-rose {
        background-color: #E6C2BF;
    }

    .bg-white {
        background-color: #FFF;
    }

    /* ENGRAVING */
    .engraving-section {
        background: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 4px;
    }

    .engraving-check {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        cursor: pointer;
        margin-bottom: 10px;
    }

    .engraving-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        font-family: 'Poppins', sans-serif;
        display: none;
    }

    /* ACTIONS */
    .action-row {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        height: 45px;
    }

    .qty-selector {
        display: flex;
        align-items: center;
        border: 1px solid #ccc;
        padding: 0 10px;
    }

    .qty-btn {
        background: none;
        border: none;
        font-size: 16px;
        cursor: pointer;
        padding: 0 10px;
        color: black;
    }

    .qty-input {
        width: 40px;
        text-align: center;
        border: none;
        background: transparent;
        font-family: 'Poppins';
    }

    .btn-add-bag {
        flex: 1;
        background: #000;
        color: #fff;
        border: none;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 1px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-add-bag:hover {
        background: #333;
    }

    /* TEXT CONTENT */
    .description {
        font-size: 13px;
        line-height: 1.6;
        color: #444;
        margin-bottom: 20px;
    }

    .details-list {
        font-size: 12px;
        color: #555;
        list-style-position: inside;
    }

    .details-list li {
        margin-bottom: 4px;
    }

    /* DESIGNER SECTION */
    .designer-section {
        padding: 60px;
    }

    .section-header {
        margin-bottom: 30px;
        font-size: 20px;
    }

    .designer-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .mini-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        margin-bottom: 10px;
    }

    .mini-card h5 {
        font-size: 13px;
        margin-bottom: 5px;
    }

    .mini-card p {
        font-size: 12px;
        color: #666;
    }

    /* REVIEWS */
    .reviews-section {
        padding: 40px 60px;
        max-width: 900px;
    }

    .review-card {
        border-bottom: 1px solid #ddd;
        padding: 20px 0;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .stars {
        color: #f4c150;
        font-size: 12px;
    }

    .review-user {
        font-weight: 500;
        font-size: 13px;
    }

    .review-date {
        font-size: 11px;
        color: #888;
    }

    .review-text {
        font-size: 13px;
        line-height: 1.5;
        color: #444;
    }

    /* FOOTER */
    footer {
        background: #f8f4ec;
        padding: 60px 80px 20px;
        font-size: 13px;
        border-top: 1px solid #ddd;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
    }

    .footer-grid h4 {
        margin-bottom: 12px;
        font-family: 'Playfair Display';
    }

    .footer-bottom {
        text-align: center;
        margin-top: 40px;
        font-size: 12px;
        color: #777;
    }

    @media(max-width: 900px) {
        .product-container {
            grid-template-columns: 1fr;
            padding: 20px;
        }

        .gallery-wrapper {
            height: auto;
            grid-template-columns: 1fr;
        }

        .thumbnails {
            flex-direction: row;
            order: 2;
            margin-top: 10px;
        }

        .thumbnails img {
            width: 60px;
        }

        .designer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    </style>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="breadcrumbs">
        <a href="index.php">Home</a> /
        <a href="catalog.php">Catalog</a> /
        <?= htmlspecialchars($product['ITEM_CATEGORY']) ?>
    </div>

    <div class="product-container">

        <div class="gallery-wrapper">
            <div class="thumbnails">
                <?php foreach ($gallery_images as $index => $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" class="<?= $index === 0 ? 'active' : '' ?>"
                    onclick="changeImage('<?= htmlspecialchars($img) ?>', this)">
                <?php endforeach; ?>
            </div>
            <div class="main-image">
                <img id="mainImg" src="<?= htmlspecialchars($gallery_images[0] ?? '') ?>" alt="Product Image">
            </div>
        </div>

        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($product['ITEM_NAME']) ?></h1>
            <div class="designer-name">Designed by: <?= htmlspecialchars($product['DESIGNER_NAME']) ?></div>
            <div class="product-price">RM <?= number_format($product['ITEM_PRICE'], 0) ?></div>

            <?php if (count($variants) > 1): ?>
            <div class="variants-section">
                <div style="font-size: 12px; margin-bottom: 5px;">Color / Material</div>
                <div class="swatches">
                    <?php foreach ($variants as $var):
                            // Logic to pick color for swatch
                            $mat = strtolower($var['ITEM_MATERIAL'] . $var['ITEM_COLOR']);
                            $bgClass = 'bg-silver'; // Default
                            if (strpos($mat, 'gold') !== false) $bgClass = 'bg-gold';
                            if (strpos($mat, 'rose') !== false) $bgClass = 'bg-rose';

                            $isActive = ($var['ITEM_ID'] == $item_id) ? 'active' : '';
                        ?>
                    <a href="product_detail.php?id=<?= $var['ITEM_ID'] ?>"
                        class="swatch <?= $bgClass ?> <?= $isActive ?>"
                        title="<?= htmlspecialchars($var['ITEM_MATERIAL']) ?>">
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form action="cart_add.php" method="POST">
                <input type="hidden" name="item_id" value="<?= $item_id ?>">

                <?php if ($product['IS_ENGRAVABLE']): ?>
                <div class="engraving-section">
                    <label class="engraving-check">
                        <input type="checkbox" id="engraveCheck" onclick="toggleEngraving()">
                        Engraving +RM 5.00
                    </label>
                    <input type="text" name="engraving_text" id="engraveInput" class="engraving-input"
                        placeholder="Enter text (Max 10 chars)" maxlength="10">
                </div>
                <?php endif; ?>

                <div class="action-row">
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" class="qty-input" readonly>
                        <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                    </div>
                    <button type="submit" class="btn-add-bag">
                        <i class="fa-solid fa-bag-shopping"></i> Add to bag
                    </button>
                </div>
            </form>

            <div class="description">
                <strong>Item Description</strong><br>
                <?= nl2br(htmlspecialchars($product['ITEM_DESCRIPTION'])) ?>
            </div>

            <div class="description">
                <strong>Item Details</strong>
                <ul class="details-list">
                    <li>Material: <?= htmlspecialchars($product['ITEM_MATERIAL']) ?></li>
                    <?php if ($product['ITEM_WEIGHT']) echo "<li>Weight: {$product['ITEM_WEIGHT']}g</li>"; ?>
                    <?php if ($product['ITEM_SIZE']) echo "<li>Size: {$product['ITEM_SIZE']}</li>"; ?>
                    <li>Waterproof & suitable for daily wear</li>
                    <li>Lightweight and comfortable</li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (!empty($related_items)): ?>
    <div class="designer-section">
        <h3 class="section-header">Designed By <?= htmlspecialchars($product['DESIGNER_NAME']) ?></h3>
        <div class="designer-grid">
            <?php foreach ($related_items as $related): ?>
            <a href="product_detail.php?id=<?= $related['ITEM_ID'] ?>" class="mini-card">
                <img src="<?= htmlspecialchars($related['ITEM_IMAGE']) ?>" alt="Rel">
                <h5><?= htmlspecialchars($related['ITEM_NAME']) ?></h5>
                <p>RM <?= number_format($related['ITEM_PRICE'], 0) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="reviews-section">
        <h3 class="section-header">Reviews (<?= count($reviews) ?>)</h3>

        <?php if (empty($reviews)): ?>
        <p style="font-size: 13px; color: #777;">No reviews yet. Be the first to review!</p>
        <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
            <div class="review-header">
                <div class="review-user">
                    <?= htmlspecialchars($rev['CUSTOMER_NAME']) ?>
                    <div class="stars">
                        <?php for ($i = 0; $i < $rev['REVIEW_RATING']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                    </div>
                </div>
                <div class="review-date"><?= date('M d, Y', strtotime($rev['REVIEW_DATE'])) ?></div>
            </div>
            <div class="review-text">
                <?= htmlspecialchars($rev['REVIEW_TEXT']) ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'components/footer.php'; ?>


    <script>
    function changeImage(src, el) {
        document.getElementById('mainImg').src = src;
        document.querySelectorAll('.thumbnails img').forEach(img => img.classList.remove('active'));
        el.classList.add('active');
    }

    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        let val = parseInt(input.value);
        val += change;
        if (val < 1) val = 1;
        input.value = val;
    }

    function toggleEngraving() {
        const input = document.getElementById('engraveInput');
        const checkbox = document.getElementById('engraveCheck');
        if (checkbox.checked) {
            input.style.display = 'block';
            input.focus();
        } else {
            input.style.display = 'none';
            input.value = '';
        }
    }
    </script>

</body>

</html>