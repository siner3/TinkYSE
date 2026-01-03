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
    $gallery_images[] = 'assets/images/placeholder.png';
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

// 5. FETCH REVIEWS (For entire product group)
$review_ids = [$item_id];
if (!empty($product['PARENT_ID'])) {
    // If it's part of a group, get reviews for ALL siblings + parent
    $grp_stmt = $pdo->prepare("SELECT ITEM_ID FROM ITEM WHERE PARENT_ID = ?");
    $grp_stmt->execute([$product['PARENT_ID']]);
    $sibling_ids = $grp_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Merge, unique, and re-index (array_values ensures sequential keys for PDO)
    $review_ids = array_values(array_unique(array_merge($review_ids, $sibling_ids)));
}

// Create placeholders for SQL IN clause (?,?,?)
$placeholders = implode(',', array_fill(0, count($review_ids), '?'));

$rev_sql = "SELECT r.*, c.CUSTOMER_NAME 
            FROM REVIEW r 
            JOIN CUSTOMER c ON r.CUSTOMER_ID = c.CUSTOMER_ID 
            WHERE r.ITEM_ID IN ($placeholders) AND r.REVIEW_ACTIVE = 1 
            ORDER BY r.REVIEW_DATE DESC";

$r_stmt = $pdo->prepare($rev_sql);
$r_stmt->execute($review_ids);
$reviews = $r_stmt->fetchAll(PDO::FETCH_ASSOC);
// 6. FETCH "MORE FROM DESIGNER"
$more_sql = "SELECT ITEM_ID, ITEM_NAME, ITEM_PRICE, ITEM_IMAGE
             FROM ITEM
             WHERE DESIGNER_ID = ? AND ITEM_ID != ? AND ITEM_ACTIVE = 1
             LIMIT 4";
$m_stmt = $pdo->prepare($more_sql);
$m_stmt->execute([$product['DESIGNER_ID'], $item_id]);
$related_items = $m_stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. FETCH LINKED CHARMS (Checks both Item ID and Parent ID)
$charms = [];

// Create a list of IDs to check: The current Item's ID AND its Parent ID (if it exists)
$ids_to_check = [$product['ITEM_ID']];
if (!empty($product['PARENT_ID'])) {
    $ids_to_check[] = $product['PARENT_ID'];
}

// Create placeholders for the SQL query (e.g., "?,?")
$placeholders = implode(',', array_fill(0, count($ids_to_check), '?'));

$charm_sql = "SELECT DISTINCT c.* FROM CHARM c
              JOIN ITEMCHARM ic ON c.CHARM_ID = ic.CHARM_ID
              WHERE ic.ITEM_ID IN ($placeholders) 
              AND c.CHARM_ACTIVE = 1
              ORDER BY c.CHARM_TYPE, c.CHARM_NAME";

$c_stmt = $pdo->prepare($charm_sql);
$c_stmt->execute($ids_to_check);
$charms = $c_stmt->fetchAll(PDO::FETCH_ASSOC);

// Show section only if charms were found
$is_charm_compatible = !empty($charms);

// 8. CHECK IF USER CAN REVIEW (Logged in + Has purchased item)
$can_review = false;
$has_already_reviewed = false;
$customer_id = null;

if (isset($_SESSION['user_id'])) {
    $customer_id = $_SESSION['user_id'];

    // Check if customer has purchased this item (or any variant in the group)
    $purchase_check_ids = $review_ids; // Reuse the same IDs from reviews section
    $purchase_placeholders = implode(',', array_fill(0, count($purchase_check_ids), '?'));

    $purchase_sql = "SELECT COUNT(*) FROM `ORDER` o
                     JOIN CART c ON o.CART_ID = c.CART_ID
                     JOIN CARTITEM ci ON c.CART_ID = ci.CART_ID
                     WHERE o.CUSTOMER_ID = ?
                     AND o.ORDER_STATUS IN ('completed', 'delivered')
                     AND ci.ITEM_ID IN ($purchase_placeholders)";

    $p_stmt = $pdo->prepare($purchase_sql);
    $p_stmt->execute(array_merge([$customer_id], $purchase_check_ids));
    $has_purchased = $p_stmt->fetchColumn() > 0;

    // Check if customer has already reviewed this item (or any variant)
    $review_check_sql = "SELECT COUNT(*) FROM REVIEW
                         WHERE CUSTOMER_ID = ? AND ITEM_ID IN ($purchase_placeholders)";
    $r_check_stmt = $pdo->prepare($review_check_sql);
    $r_check_stmt->execute(array_merge([$customer_id], $purchase_check_ids));
    $has_already_reviewed = $r_check_stmt->fetchColumn() > 0;

    // User can review if they purchased and haven't reviewed yet
    $can_review = $has_purchased && !$has_already_reviewed;
}

// Handle review submission
$review_message = '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$customer_id) {
        $review_error = 'You must be logged in to submit a review.';
    } elseif (!$can_review) {
        $review_error = 'You can only review items you have purchased.';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $review_error = 'Please select a rating between 1 and 5 stars.';
        } elseif (empty($review_text)) {
            $review_error = 'Please write a review.';
        } elseif (strlen($review_text) > 500) {
            $review_error = 'Review must be 500 characters or less.';
        } else {
            // Insert the review
            $insert_sql = "INSERT INTO REVIEW (CUSTOMER_ID, ITEM_ID, REVIEW_RATING, REVIEW_TEXT)
                           VALUES (?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_sql);

            if ($insert_stmt->execute([$customer_id, $item_id, $rating, $review_text])) {
                $review_message = 'Thank you for your review!';
                $has_already_reviewed = true;
                $can_review = false;

                // Refresh reviews list
                $r_stmt->execute($review_ids);
                $reviews = $r_stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $review_error = 'Failed to submit review. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ITEM_NAME']) ?> | TINK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/product_detail.css">
    <style>
        /* --- GLOBAL --- */
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
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #0b2239;
            font-weight: 400;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
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
            cursor: pointer !important;
            border: 2px solid transparent;
            transition: 0.2s;
            pointer-events: auto !important;
            user-select: none;
            -webkit-user-select: none;
            position: relative;
            z-index: 10;
        }

        .thumbnails img:hover {
            border-color: #7fb3c8;
        }

        .thumbnails img.active {
            border-color: #0b2239;
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
            color: #0b2239;
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
            border: 2px solid #ddd;
            cursor: pointer;
            position: relative;
            background-size: cover;
            transition: 0.2s;
        }

        .swatch:hover {
            border-color: #7fb3c8;
        }

        .swatch.active {
            border: 2px solid #0b2239;
            outline: 2px solid #0b2239;
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
            background: #fff;
        }

        .qty-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer !important;
            padding: 0 10px;
            color: #0b2239;
            font-weight: 500;
            pointer-events: auto !important;
            user-select: none;
            -webkit-user-select: none;
            min-width: 30px;
            line-height: 1;
        }

        .qty-btn:hover {
            color: #7fb3c8;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            background: transparent;
            font-family: 'Poppins';
            font-size: 14px;
        }

        .btn-add-bag {
            flex: 1;
            background: #0b2239;
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
            transition: 0.3s;
        }

        .btn-add-bag:hover {
            background: #7fb3c8;
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
            color: #0b2239;
        }

        .designer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .mini-card {
            transition: 0.3s;
        }

        .mini-card:hover {
            transform: translateY(-5px);
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
            max-width: 100%;
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

        /* CHARMS SECTION */
        .charms-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .charms-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .charms-header h4 {
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }

        .charms-total {
            font-size: 13px;
            color: #0b2239;
            font-weight: 500;
        }

        .charms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 12px;
            max-height: 250px;
            overflow-y: auto;
            padding: 5px;
        }

        .charm-item {
            position: relative;
            text-align: center;
            cursor: pointer;
            padding: 8px;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #fafafa;
        }

        .charm-item:hover {
            border-color: #7fb3c8;
            background: #fff;
        }

        .charm-item.selected {
            border-color: #0b2239;
            background: #f0f7fa;
        }

        .charm-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .charm-name {
            font-size: 10px;
            color: #333;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .charm-price {
            font-size: 10px;
            color: #666;
            font-weight: 500;
        }

        .charm-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #0b2239;
            color: #fff;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .selected-charms-list {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .selected-charms-list h5 {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .selected-charm-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f4f8;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            margin: 3px;
        }

        .selected-charm-tag .remove-charm {
            cursor: pointer;
            color: #999;
            font-size: 14px;
            line-height: 1;
        }

        .selected-charm-tag .remove-charm:hover {
            color: #c00;
        }

        .price-breakdown {
            margin-top: 15px;
            padding: 12px;
            background: #f8f8f8;
            border-radius: 6px;
            font-size: 12px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .price-row.total {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        /* REVIEW FORM STYLES */
        .review-form-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .review-form-container h4 {
            font-size: 16px;
            margin-bottom: 20px;
            color: #0b2239;
        }

        .review-form .form-group {
            margin-bottom: 20px;
        }

        .review-form label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        /* Star Rating Input */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 24px;
            color: #ddd;
            transition: color 0.2s;
            margin-bottom: 0;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input:checked~label {
            color: #f4c150;
        }

        .review-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            resize: vertical;
            min-height: 100px;
        }

        .review-form textarea:focus {
            outline: none;
            border-color: #7fb3c8;
        }

        .char-count {
            display: block;
            text-align: right;
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }

        .btn-submit-review {
            background: #0b2239;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-review:hover {
            background: #7fb3c8;
        }

        /* Review Notice */
        .review-notice {
            background: #f8f8f8;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .review-notice i {
            font-size: 24px;
            color: #999;
            margin-bottom: 10px;
            display: block;
        }

        .review-notice p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .review-notice a {
            color: #0b2239;
            font-weight: 500;
            text-decoration: underline;
        }

        .review-notice a:hover {
            color: #7fb3c8;
        }

        .review-notice-info {
            background: #e8f4f8;
            border-color: #c5e3ed;
        }

        .review-notice-info i {
            color: #0b2239;
        }

        /* Review Alerts */
        .review-alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .review-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                    <img src="<?= htmlspecialchars($img) ?>" class="thumbnail-img <?= $index === 0 ? 'active' : '' ?>"
                        data-image="<?= htmlspecialchars($img) ?>" alt="Product thumbnail">
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

            <form action="cart_add.php" method="POST" id="addToCartForm">
                <input type="hidden" name="item_id" value="<?= $item_id ?>">

                <?php if ($product['IS_ENGRAVABLE']): ?>
                    <div class="engraving-section">
                        <label class="engraving-check">
                            <input type="checkbox" id="engraveCheck">
                            Engraving +RM 5.00
                        </label>
                        <input type="text" name="engraving_text" id="engraveInput" class="engraving-input"
                            placeholder="Enter text (Max 10 chars)" maxlength="10">
                    </div>
                <?php endif; ?>

                <?php if ($is_charm_compatible && !empty($charms)): ?>
                    <div class="charms-section">
                        <div class="charms-header">
                            <h4><i class="fa-solid fa-gem"></i> Add Charms to Your Bracelet</h4>
                            <span class="charms-total">Selected: <span id="charmCount">0</span></span>
                        </div>

                        <div class="charms-grid">
                            <?php foreach ($charms as $charm):
                                $charmImg = !empty($charm['CHARM_IMAGE']) ? $charm['CHARM_IMAGE'] : 'assets/images/placeholder.png';
                            ?>
                                <div class="charm-item" data-charm-id="<?= $charm['CHARM_ID'] ?>"
                                    data-charm-name="<?= htmlspecialchars($charm['CHARM_NAME']) ?>"
                                    data-charm-price="<?= $charm['CHARM_PRICE'] ?>" onclick="toggleCharm(this)">
                                    <img src="<?= htmlspecialchars($charmImg) ?>"
                                        alt="<?= htmlspecialchars($charm['CHARM_NAME']) ?>">
                                    <div class="charm-name"><?= htmlspecialchars($charm['CHARM_NAME']) ?></div>
                                    <div class="charm-price">+RM <?= number_format($charm['CHARM_PRICE'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="selected-charms-list" id="selectedCharmsList" style="display: none;">
                            <h5>Your Selected Charms:</h5>
                            <div id="selectedCharmsContainer"></div>
                        </div>

                        <div class="price-breakdown" id="priceBreakdown">
                            <div class="price-row">
                                <span>Bracelet</span>
                                <span>RM <?= number_format($product['ITEM_PRICE'], 2) ?></span>
                            </div>
                            <div class="price-row" id="charmsRow" style="display: none;">
                                <span>Charms (<span id="charmQty">0</span>)</span>
                                <span>RM <span id="charmsPrice">0.00</span></span>
                            </div>
                            <div class="price-row total">
                                <span>Total</span>
                                <span>RM <span id="totalPrice"><?= number_format($product['ITEM_PRICE'], 2) ?></span></span>
                            </div>
                        </div>

                        <!-- Hidden inputs for selected charms -->
                        <div id="charmInputs"></div>
                    </div>
                <?php endif; ?>

                <div class="action-row">
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" id="qtyMinus">−</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" class="qty-input" readonly>
                        <button type="button" class="qty-btn" id="qtyPlus">+</button>
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
            <h3 class="section-header">More from <?= htmlspecialchars($product['DESIGNER_NAME']) ?></h3>
            <div class="designer-grid">
                <?php foreach ($related_items as $related): ?>
                    <a href="product_detail.php?id=<?= $related['ITEM_ID'] ?>" class="mini-card">
                        <img src="<?= htmlspecialchars($related['ITEM_IMAGE']) ?>"
                            alt="<?= htmlspecialchars($related['ITEM_NAME']) ?>">
                        <h5><?= htmlspecialchars($related['ITEM_NAME']) ?></h5>
                        <p>RM <?= number_format($related['ITEM_PRICE'], 0) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="reviews-section">
        <h3 class="section-header">Reviews (<?= count($reviews) ?>)</h3>

        <!-- Review Form Section -->
        <?php if ($review_message): ?>
            <div class="review-alert review-success">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($review_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($review_error): ?>
            <div class="review-alert review-error">
                <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($review_error) ?>
            </div>
        <?php endif; ?>

        <?php if ($can_review): ?>
            <!-- User can write a review -->
            <div class="review-form-container">
                <h4>Write a Review</h4>
                <form method="POST" class="review-form">
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                <label for="star<?= $i ?>"><i class="fa-solid fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="review_text">Your Review</label>
                        <textarea name="review_text" id="review_text" rows="4"
                            placeholder="Share your experience with this product..." maxlength="500" required></textarea>
                        <small class="char-count"><span id="charCount">0</span>/500 characters</small>
                    </div>
                    <button type="submit" name="submit_review" class="btn-submit-review">
                        <i class="fa-solid fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            </div>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <!-- User not logged in -->
            <div class="review-notice">
                <i class="fa-solid fa-lock"></i>
                <p>Please <a href="login.php">log in</a> to write a review.</p>
            </div>
        <?php elseif ($has_already_reviewed): ?>
            <!-- User already reviewed -->
            <div class="review-notice review-notice-info">
                <i class="fa-solid fa-check-circle"></i>
                <p>You have already reviewed this product. Thank you!</p>
            </div>
        <?php else: ?>
            <!-- User logged in but hasn't purchased -->
            <div class="review-notice">
                <i class="fa-solid fa-shopping-bag"></i>
                <p>Only customers who have purchased this item can write a review.</p>
            </div>
        <?php endif; ?>

        <!-- Existing Reviews -->
        <?php if (empty($reviews)): ?>
            <p style="font-size: 13px; color: #777; margin-top: 20px;">No reviews yet. Be the first to review!</p>
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

    <!-- JAVASCRIPT MUST COME BEFORE FOOTER -->
    <script>
        // ===== CHARM SELECTION FUNCTIONALITY =====
        const basePrice = <?= $product['ITEM_PRICE'] ?>;
        let selectedCharms = [];

        function toggleCharm(element) {
            const charmId = element.dataset.charmId;
            const charmName = element.dataset.charmName;
            const charmPrice = parseFloat(element.dataset.charmPrice);

            const existingIndex = selectedCharms.findIndex(c => c.id === charmId);

            if (existingIndex > -1) {
                // Remove charm
                selectedCharms.splice(existingIndex, 1);
                element.classList.remove('selected');
            } else {
                // Add charm
                selectedCharms.push({
                    id: charmId,
                    name: charmName,
                    price: charmPrice
                });
                element.classList.add('selected');
            }

            updateCharmUI();
        }

        function removeCharm(charmId) {
            const index = selectedCharms.findIndex(c => c.id === charmId);
            if (index > -1) {
                selectedCharms.splice(index, 1);
                // Remove selected class from grid item
                const gridItem = document.querySelector(`.charm-item[data-charm-id="${charmId}"]`);
                if (gridItem) gridItem.classList.remove('selected');
                updateCharmUI();
            }
        }

        function updateCharmUI() {
            const charmCount = document.getElementById('charmCount');
            const charmQty = document.getElementById('charmQty');
            const charmsPrice = document.getElementById('charmsPrice');
            const totalPrice = document.getElementById('totalPrice');
            const charmsRow = document.getElementById('charmsRow');
            const selectedCharmsList = document.getElementById('selectedCharmsList');
            const selectedCharmsContainer = document.getElementById('selectedCharmsContainer');
            const charmInputs = document.getElementById('charmInputs');

            // Calculate totals
            const totalCharmPrice = selectedCharms.reduce((sum, c) => sum + c.price, 0);
            const grandTotal = basePrice + totalCharmPrice;

            // Update counts and prices
            if (charmCount) charmCount.textContent = selectedCharms.length;
            if (charmQty) charmQty.textContent = selectedCharms.length;
            if (charmsPrice) charmsPrice.textContent = totalCharmPrice.toFixed(2);
            if (totalPrice) totalPrice.textContent = grandTotal.toFixed(2);

            // Show/hide charms row
            if (charmsRow) {
                charmsRow.style.display = selectedCharms.length > 0 ? 'flex' : 'none';
            }

            // Update selected charms list
            if (selectedCharmsList && selectedCharmsContainer) {
                if (selectedCharms.length > 0) {
                    selectedCharmsList.style.display = 'block';
                    selectedCharmsContainer.innerHTML = selectedCharms.map(c =>
                        `<span class="selected-charm-tag">
                            ${c.name} (RM ${c.price.toFixed(2)})
                            <span class="remove-charm" onclick="removeCharm('${c.id}')">&times;</span>
                        </span>`
                    ).join('');
                } else {
                    selectedCharmsList.style.display = 'none';
                    selectedCharmsContainer.innerHTML = '';
                }
            }

            // Update hidden form inputs
            if (charmInputs) {
                charmInputs.innerHTML = selectedCharms.map(c =>
                    `<input type="hidden" name="charms[]" value="${c.id}">`
                ).join('');
            }
        }

        console.log('🚀 TINK Product Detail Script Loading...');

        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM Content Loaded');

            // ===== IMAGE GALLERY FUNCTIONALITY =====
            const thumbnails = document.querySelectorAll('.thumbnail-img');
            const mainImg = document.getElementById('mainImg');

            console.log('📸 Found ' + thumbnails.length + ' thumbnails');
            console.log('🖼️ Main image element:', mainImg);

            if (thumbnails.length > 0 && mainImg) {
                thumbnails.forEach(function(thumb, index) {
                    console.log('🔗 Attaching click listener to thumbnail ' + (index + 1));

                    // Add click event listener
                    thumb.addEventListener('click', function(e) {
                        console.log('🖱️ Thumbnail clicked!', index + 1);
                        e.preventDefault();
                        e.stopPropagation();

                        // Get the image source
                        const newSrc = this.getAttribute('data-image') || this.src;
                        console.log('📂 New image source:', newSrc);

                        // Update main image
                        mainImg.src = newSrc;

                        // Update active state
                        thumbnails.forEach(function(t) {
                            t.classList.remove('active');
                        });
                        this.classList.add('active');

                        console.log('✅ Image changed successfully');
                    });

                    // Also add visual feedback
                    thumb.style.cursor = 'pointer';
                });
                console.log('✅ All thumbnail listeners attached');
            }

            // ===== QUANTITY CONTROLS =====
            const qtyInput = document.getElementById('qtyInput');
            const qtyMinus = document.getElementById('qtyMinus');
            const qtyPlus = document.getElementById('qtyPlus');

            console.log('🔍 Qty elements found:', {
                input: !!qtyInput,
                minus: !!qtyMinus,
                plus: !!qtyPlus
            });

            if (qtyInput && qtyMinus && qtyPlus) {
                // Decrease quantity
                qtyMinus.addEventListener('click', function(e) {
                    console.log('🖱️ Minus button clicked');
                    e.preventDefault();
                    e.stopPropagation();

                    let currentValue = parseInt(qtyInput.value) || 1;
                    console.log('Current qty:', currentValue);

                    if (currentValue > 1) {
                        qtyInput.value = currentValue - 1;
                        console.log('✅ Decreased to:', qtyInput.value);
                    } else {
                        console.log('⚠️ Cannot go below 1');
                    }
                });

                // Increase quantity
                qtyPlus.addEventListener('click', function(e) {
                    console.log('🖱️ Plus button clicked');
                    e.preventDefault();
                    e.stopPropagation();

                    let currentValue = parseInt(qtyInput.value) || 1;
                    console.log('Current qty:', currentValue);

                    qtyInput.value = currentValue + 1;
                    console.log('✅ Increased to:', qtyInput.value);
                });

                // Make buttons visually interactive
                qtyMinus.style.cursor = 'pointer';
                qtyPlus.style.cursor = 'pointer';

                console.log('✅ Quantity controls initialized');
            }

            // ===== ENGRAVING TOGGLE =====
            const engraveCheck = document.getElementById('engraveCheck');
            const engraveInput = document.getElementById('engraveInput');

            if (engraveCheck && engraveInput) {
                console.log('✅ Engraving elements found');

                engraveCheck.addEventListener('change', function() {
                    console.log('📝 Engraving checkbox changed:', this.checked);

                    if (this.checked) {
                        engraveInput.style.display = 'block';
                        engraveInput.focus();
                    } else {
                        engraveInput.style.display = 'none';
                        engraveInput.value = '';
                    }
                });
            }

            // ===== REVIEW FORM CHARACTER COUNTER =====
            const reviewTextarea = document.getElementById('review_text');
            const charCount = document.getElementById('charCount');

            if (reviewTextarea && charCount) {
                reviewTextarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }

            console.log('🎉 All JavaScript initialized successfully!');
        });

        // Fallback: Also try without DOMContentLoaded
        setTimeout(function() {
            console.log('⏰ Timeout check - ensuring everything is working');

            // Double-check thumbnails
            const thumbs = document.querySelectorAll('.thumbnail-img');
            if (thumbs.length > 0) {
                console.log('✅ Thumbnails still accessible');
            }
        }, 1000);
    </script>

    <?php include 'components/footer.php'; ?>

</body>

</html>