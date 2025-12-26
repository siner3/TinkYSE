<?php
session_start();
require_once '../config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 2. INIT VARS
$success_msg = '';
$error_msg = '';

// --- CONFIGURATION ---
$tag_categories = [
    'Gender'     => ['Women', 'Men', 'Unisex', 'Kids'],
    'Style'      => ['Minimalist', 'Statement', 'Layered', 'Classic', 'Luxury', 'Chunky'],
    'Aesthetics' => ['Vintage', 'Boho', 'Modern', 'Y2K', 'Coquette', 'Old Money', 'Grunge'],
    'Occasion'   => ['Wedding', 'Party', 'Daily Wear', 'Gift', 'Anniversary'],
    'Features'   => ['Waterproof', 'Hypoallergenic', 'Tarnish-Free', 'Adjustable']
];

// --- FETCH DATA ---
$designers_list = $pdo->query("SELECT * FROM DESIGNER ORDER BY DESIGNER_NAME")->fetchAll(PDO::FETCH_ASSOC);
$parent_items = $pdo->query("SELECT ITEM_ID, ITEM_NAME FROM ITEM WHERE PARENT_ID IS NULL ORDER BY ITEM_NAME")->fetchAll(PDO::FETCH_ASSOC);

// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'save_product') {
            // 1. COMMON DATA
            $item_desc = trim($_POST['item_description'] ?? '');
            $item_cat = $_POST['item_category'] ?? '';
            $designer_id = intval($_POST['designer_id'] ?? 0);
            $is_engravable = isset($_POST['is_engravable']) ? 1 : 0;

            // Parent ID Logic
            $existing_parent_id = null;
            if ($item_cat === 'Charms' && !empty($_POST['parent_id'])) {
                $existing_parent_id = intval($_POST['parent_id']);
            }

            // Tags
            $selected_tags = $_POST['item_tags_select'] ?? [];
            $custom_tags = trim($_POST['item_tags_custom'] ?? '');
            if ($custom_tags) {
                foreach (explode(',', $custom_tags) as $ct) $selected_tags[] = trim($ct);
            }
            $item_tags = implode(', ', array_unique(array_filter($selected_tags)));

            // 2. VARIANT DATA
            $names = $_POST['var_name'] ?? [];
            $materials = $_POST['var_material'] ?? [];
            $prices = $_POST['var_price'] ?? [];
            $stocks = $_POST['var_stock'] ?? [];
            $ids = $_POST['var_id'] ?? [];

            // 3. GROUPING LOGIC
            if (empty($existing_parent_id) && !empty($ids[0])) {
                $check_stmt = $pdo->prepare("SELECT PARENT_ID FROM ITEM WHERE ITEM_ID = ?");
                $check_stmt->execute([intval($ids[0])]);
                $db_parent = $check_stmt->fetchColumn();
                if ($db_parent) $existing_parent_id = $db_parent;
            }

            $final_parent_id = $existing_parent_id;
            if (empty($final_parent_id) && count($names) > 1) {
                $final_parent_id = rand(10000, 99999);
            }
            if (count($names) === 1 && empty($existing_parent_id)) {
                $final_parent_id = null;
            }

            // 4. GALLERY IMAGES UPLOAD
            $final_gallery = [];
            if (!empty($_POST['existing_gallery_images'])) {
                $final_gallery = json_decode($_POST['existing_gallery_images'], true) ?? [];
            }

            $upload_dir = dirname(__DIR__) . '/images/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (!empty($_FILES['base_item_images']['name'][0])) {
                for ($i = 0; $i < count($_FILES['base_item_images']['name']); $i++) {
                    if (!empty($_FILES['base_item_images']['name'][$i])) {
                        $ext = strtolower(pathinfo($_FILES['base_item_images']['name'][$i], PATHINFO_EXTENSION));
                        $filename = 'base_' . time() . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($_FILES['base_item_images']['tmp_name'][$i], $upload_dir . $filename)) {
                            $final_gallery[] = 'images/products/' . $filename;
                        }
                    }
                }
            }
            $gallery_json = !empty($final_gallery) ? json_encode(array_values($final_gallery)) : null;

            // --- NEW: FALLBACK IMAGE LOGIC ---
            // If we have gallery images, pick the first one as the default "Product Image"
            $gallery_fallback_image = !empty($final_gallery) ? $final_gallery[0] : null;

            // 5. SAVE / UPDATE VARIANTS
            $saved_ids = [];

            for ($i = 0; $i < count($names); $i++) {
                $v_name = trim($names[$i]);
                $v_mat = trim($materials[$i]);
                $v_price = floatval($prices[$i]);
                $v_stock = intval($stocks[$i]);
                $v_id = !empty($ids[$i]) ? intval($ids[$i]) : null;

                $image_path = null;
                $has_uploaded_new_image = false;

                // Check specific upload
                if (!empty($_FILES['var_image']['name'][$i])) {
                    $ext = strtolower(pathinfo($_FILES['var_image']['name'][$i], PATHINFO_EXTENSION));
                    $filename = 'var_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['var_image']['tmp_name'][$i], $upload_dir . $filename)) {
                        $image_path = 'images/products/' . $filename;
                        $has_uploaded_new_image = true;
                    }
                }

                // --- KEY FIX: USE GALLERY IMAGE IF NO SPECIFIC IMAGE ---
                // If user didn't upload a specific variant image, use the first gallery image
                if (!$has_uploaded_new_image && $gallery_fallback_image) {
                    $image_path = $gallery_fallback_image;
                }

                if ($v_id) {
                    // UPDATE
                    $sql = "UPDATE ITEM SET DESIGNER_ID=?, ITEM_CATEGORY=?, ITEM_NAME=?, ITEM_DESCRIPTION=?, ITEM_MATERIAL=?, ITEM_PRICE=?, ITEM_STOCK=?, ITEM_TAGS=?, PARENT_ID=?, IS_ENGRAVABLE=?, GALLERY_IMAGES=?";
                    $params = [$designer_id, $item_cat, $v_name, $item_desc, $v_mat, $v_price, $v_stock, $item_tags, $final_parent_id, $is_engravable, $gallery_json];

                    // Only update image column if we actually have an image path (from upload or fallback)
                    // This ensures we populate it if it was missing, or update it if changed.
                    if ($image_path) {
                        $sql .= ", ITEM_IMAGE=?";
                        $params[] = $image_path;
                    }

                    $sql .= " WHERE ITEM_ID=?";
                    $params[] = $v_id;
                    $pdo->prepare($sql)->execute($params);
                    $saved_ids[] = $v_id;
                } else {
                    // INSERT
                    $stmt = $pdo->prepare("INSERT INTO ITEM (DESIGNER_ID, ITEM_CATEGORY, ITEM_NAME, ITEM_DESCRIPTION, ITEM_MATERIAL, ITEM_PRICE, ITEM_STOCK, ITEM_IMAGE, ITEM_TAGS, PARENT_ID, IS_ENGRAVABLE, GALLERY_IMAGES) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$designer_id, $item_cat, $v_name, $item_desc, $v_mat, $v_price, $v_stock, $image_path, $item_tags, $final_parent_id, $is_engravable, $gallery_json]);
                    $saved_ids[] = $pdo->lastInsertId();
                }
            }

            // 6. PRUNE DELETED VARIANTS
            if ($final_parent_id) {
                $stmt = $pdo->prepare("SELECT ITEM_ID FROM ITEM WHERE PARENT_ID = ?");
                $stmt->execute([$final_parent_id]);
                $db_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $ids_to_remove = array_diff($db_ids, $saved_ids);
                if (!empty($ids_to_remove)) {
                    $in_remove = implode(',', array_fill(0, count($ids_to_remove), '?'));
                    $pdo->prepare("DELETE FROM CARTITEM WHERE ITEM_ID IN ($in_remove)")->execute(array_values($ids_to_remove));
                    $pdo->prepare("DELETE FROM ITEM WHERE ITEM_ID IN ($in_remove)")->execute(array_values($ids_to_remove));
                }
            }

            $success_msg = "Product(s) saved successfully!";
        }

        if ($action === 'delete_product') {
            $target_id = intval($_POST['item_id']);
            $stmt = $pdo->prepare("SELECT PARENT_ID FROM ITEM WHERE ITEM_ID = ?");
            $stmt->execute([$target_id]);
            $parent_id = $stmt->fetchColumn();

            if ($parent_id) {
                $stmt = $pdo->prepare("SELECT ITEM_ID FROM ITEM WHERE PARENT_ID = ?");
                $stmt->execute([$parent_id]);
            } else {
                $stmt = $pdo->prepare("SELECT ITEM_ID FROM ITEM WHERE ITEM_ID = ?");
                $stmt->execute([$target_id]);
            }
            $ids_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids_to_delete)) {
                $in_query = implode(',', array_fill(0, count($ids_to_delete), '?'));
                $pdo->prepare("DELETE FROM CARTITEM WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM REVIEW WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM ITEM_GALLERY WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM ITEMCHARM WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM ITEM WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $success_msg = "Product(s) deleted successfully!";
            }
        }
    } catch (Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// --- FETCH LIST ---
$where_clauses = ["1=1"];
$params = [];
if (!empty($_GET['search'])) {
    $where_clauses[] = "(ITEM_NAME LIKE ? OR ITEM_DESCRIPTION LIKE ?)";
    $params[] = "%$_GET[search]%";
    $params[] = "%$_GET[search]%";
}

$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;
$sort_by = $_GET['sort'] ?? 'item_id_desc';
$sort_map = ['item_id_desc' => 'ORDER BY i.ITEM_ID DESC', 'name_asc' => 'ORDER BY i.ITEM_NAME ASC', 'price_low' => 'ORDER BY i.ITEM_PRICE ASC', 'price_high' => 'ORDER BY i.ITEM_PRICE DESC', 'stock_low' => 'ORDER BY i.ITEM_STOCK ASC'];
$order_clause = $sort_map[$sort_by] ?? $sort_map['item_id_desc'];

$where_sql = implode(' AND ', $where_clauses);

$total_items = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(PARENT_ID, ITEM_ID)) FROM ITEM i WHERE $where_sql");
$total_items->execute($params);
$total_items = $total_items->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$sql = "SELECT i.*, d.DESIGNER_NAME, COUNT(*) as variant_count 
        FROM ITEM i 
        LEFT JOIN DESIGNER d ON i.DESIGNER_ID = d.DESIGNER_ID 
        WHERE $where_sql 
        GROUP BY COALESCE(i.PARENT_ID, i.ITEM_ID) 
        $order_clause 
        LIMIT $items_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog Management - TINK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .variant-badge {
            font-size: 0.7rem;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 4px;
            display: inline-block;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }

        .modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 0;
            max-width: 950px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #f1f5f9;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #0f172a;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            width: 100%;
        }

        .select2-container--default .select2-selection--single {
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 7px;
        }

        #parentIdContainer {
            display: none;
            transition: all 0.3s ease;
        }

        .tags-scroll-container {
            max-height: 250px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
        }

        .tag-category-group {
            margin-bottom: 18px;
            border-bottom: 1px dashed #e5e7eb;
            padding-bottom: 12px;
        }

        .tag-category-group:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .tag-category-title {
            font-size: 0.7rem;
            font-weight: 800;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            display: block;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }

        .checkbox-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #374151;
            cursor: pointer;
            padding: 4px 0;
            white-space: nowrap;
        }

        .checkbox-item input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }

        .tag-input-group {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .tag-input-group select {
            width: 120px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .tag-input-group input {
            flex: 1;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .tag-add-btn {
            padding: 0 16px;
            background: #18181b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tag-add-btn:hover {
            background: #333;
        }

        .gallery-upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: 0.2s;
        }

        .gallery-upload-area:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .gallery-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            cursor: zoom-in;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-item-remove {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #imageLightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s;
        }

        #imageLightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 4px;
        }

        .variant-container {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
        }

        .variant-header {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr 0.5fr 40px;
            gap: 12px;
            background: #f8fafc;
            padding: 10px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .variant-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .variant-row {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr 0.5fr 40px;
            gap: 12px;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }

        .variant-row input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .row-upload-btn {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }

        .row-upload-btn input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .row-upload-btn.has-file {
            background: #dcfce7;
            color: #16a34a;
            border-color: #86efac;
        }

        .btn-add-variant {
            width: 100%;
            padding: 12px;
            border: none;
            border-top: 1px solid #e2e8f0;
            background: #fff;
            color: #3b82f6;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-add-variant:hover {
            background: #f8fafc;
        }

        .btn-remove-variant {
            color: #ef4444;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .btn-submit {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-cancel {
            padding: 10px 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            cursor: pointer;
        }

        .stock-high {
            color: #16a34a;
            background: #dcfce7;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-low {
            color: #dc2626;
            background: #fee2e2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .category-badge {
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #4b5563;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div id="imageLightbox" onclick="this.style.display='none'">
        <img id="lightboxImg" src="" alt="Full Preview">
    </div>

    <aside class="sidebar">
        <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 288 149.67">
                <defs>
                    <style>
                        .cls-1 {
                            fill: #000;
                            stroke-width: 0px;
                        }
                    </style>
                </defs>
                <path class="cls-1"
                    d="M108.85,55.5h-.66c-4.05-14.45-12.56-14.49-23.14-14.49v66.68c0,5.53,5.29,9.31,10.02,9.31v.93h-36.91v-.93c4.73,0,10.02-3.78,10.02-9.31V41.01c-10.57,0-19.15.04-23.19,14.49h-.68l.24-15.54h64.13l.18,15.54Z" />
                <path class="cls-1"
                    d="M123.07,61.07c-4.47.41-5.34,1.44-5.69,6.69-.01.2-.3.2-.31,0-.35-5.25-1.22-6.28-5.69-6.69-.22-.02-.22-.35,0-.37,4.47-.41,5.34-1.44,5.69-6.69.01-.2.3-.2.31,0,.35,5.25,1.22,6.28,5.69,6.69.22.02.22.35,0,.37Z" />
                <path class="cls-1"
                    d="M49.34,35.16c-1.32.12-1.57.43-1.68,1.99,0,.06-.09.06-.09,0-.1-1.57-.36-1.87-1.68-1.99-.07,0-.07-.1,0-.11,1.32-.12,1.57-.43,1.68-1.99,0-.06.09-.06.09,0,.1,1.57.36,1.87,1.68,1.99.07,0,.07.1,0,.11Z" />
                <path class="cls-1"
                    d="M39.75,41.47c-1.52.15-1.82.52-1.94,2.43,0,.07-.1.07-.11,0-.12-1.91-.41-2.28-1.94-2.43-.08,0-.08-.13,0-.13,1.52-.15,1.82-.52,1.94-2.43,0-.07.1-.07.11,0,.12,1.91.41,2.28,1.94,2.43.08,0,.08.13,0,.13Z" />
                <path class="cls-1"
                    d="M44.89,34.59c-2.86.26-3.42.89-3.64,4.16,0,.12-.19.12-.2,0-.22-3.27-.78-3.9-3.64-4.16-.14-.01-.14-.22,0-.23,2.86-.26,3.42-.89,3.64-4.16,0-.12.19-.12.2,0,.22,3.27.78,3.9,3.64,4.16.14.01.14.22,0,.23Z" />
                <path class="cls-1"
                    d="M140.41,111.44h0c-3.72,2.98-10.76,8.06-16.25,8.06-6.75,0-11.59-5.28-11.81-11.28v-24.56c-.21-3.4-1.68-6.97-6.83-6.97v-.82c9.34-1.17,20.13-6.58,20.13-6.58h.68v36.26c.28,4.99,2.5,8.07,6.49,8.07,3.09,0,4.82-1.22,6.94-3.11h0c.09-.07.2-.12.33-.12.31,0,.55.26.55.58,0,.19-.1.36-.24.46Z" />
                <path class="cls-1"
                    d="M255.75,111.12c0,.2-.1.38-.24.47-.49.38-1.02.82-1.6,1.27-.26.18-.53.38-.8.58-.7.52-1.48,1.06-2.28,1.59-.38.25-.76.5-1.15.74-3.31,2.04-7.06,3.79-10.27,3.79-6.75,0-8.9-4.58-11.81-11.28l-5.52-13.94-8.45,8.27v2.97c.28,4.99,2.5,8.07,6.49,8.07,3.09,0,4.82-1.22,6.94-3.11h0c.09-.07.2-.12.33-.12.31,0,.55.26.55.58,0,.19-.1.36-.24.46h0c-3.72,2.98-10.76,8.06-16.25,8.06-6.75,0-11.59-5.28-11.81-11.28v-54.39c-.21-3.4-1.68-6.97-6.83-6.97v-.82c9.34-1.17,20.13-6.58,20.13-6.58h.68v61.69l8.04-7.84,9.34-9.11c.66-.65,1.23-1.3,1.69-2,.88-1.3,1.36-2.77,1.36-4.7,0-3.64-1.8-6.34-6.53-6.34,0,0-.25-.06-.25-.43s.25-.5.25-.5h23.62s.33.13.33.48-.33.46-.33.46c-5.06.11-13.73,7.16-17.55,11.86l-.42.41,8.36,21.07c1.52,3.85,3.8,8.19,6.52,9.16,1.97.69,3.62-.62,5.06-1.6.37-.24.72-.52,1.08-.83l.68-.57h.01c.09-.09.19-.13.31-.13.3,0,.54.25.54.56Z" />
                <path class="cls-1"
                    d="M199.23,111.1c0,.2-.1.38-.25.47-.48.38-1.01.82-1.59,1.28-.26.18-.54.38-.8.57-.71.53-1.48,1.06-2.28,1.59-.38.25-.76.49-1.15.73-3.31,2.04-7.06,3.8-10.27,3.8-6.75,0-11.59-5.28-11.81-11.28v-23.93c0-5.64-2.25-9.16-6.51-9.16-3.63,0-5.65,1.91-8.27,4.3v26.07c.29,5,2.5,8.07,6.49,8.07,3.09,0,4.82-1.22,6.94-3.11.1-.07.21-.12.33-.12.31,0,.55.26.55.58,0,.19-.09.35-.23.46h0c-3.72,2.97-10.76,8.06-16.25,8.06-6.74,0-11.59-5.28-11.81-11.28v-24.56c-.07-1.19-.3-2.39-.78-3.45,3.45-1.15,5.27-5.13,5.27-5.13,0,0,0-.01-.01-.02.1-.27.38-1.12.54-2.26,4.77-1.74,8.29-3.5,8.29-3.5h.67v8.61c3.48-2.84,11.08-8.6,16.93-8.6,6.75,0,11.59,5.28,11.81,11.28v17.34h-.01v6.59c0,5.64,2.25,9.16,6.52,9.16,2.07,0,3.62-.63,5.05-1.6.36-.24.71-.52,1.09-.83l.68-.57h.01c.1-.08.19-.12.31-.12.31,0,.55.26.55.56Z" />
                <path class="cls-1"
                    d="M146.66,67.66c-1.72-3.43-6.09-4.94-6.09-4.94,0,0-.1.25-.22.65-.37-.16-.6-.25-.6-.25,0,0-1.6,4.05.12,7.49.33.66.76,1.26,1.24,1.77-.72-.13-1.46-.18-2.21-.08-3.9.52-6.41,4.66-6.41,4.66,0,0,.34.29.93.69-.25.45-.38.74-.38.74,0,0,2.12,1.48,4.73,2,1.04.21,2.16.26,3.25-.01.19-.04.36-.1.54-.17,3.45-1.15,5.27-5.13,5.27-5.13,0,0,0-.01-.01-.02.1-.27.38-1.12.54-2.26.2-1.46.2-3.37-.68-5.13ZM140.51,70.33c-.96-1.96-.76-4.19-.46-5.59-.24,1.49-.31,3.58.64,5.46.54,1.09,1.35,1.97,2.18,2.68-.13-.04-.26-.09-.39-.15-.78-.64-1.48-1.43-1.96-2.4ZM133.53,76.82c.78-1.05,2.76-3.41,5.45-3.78.02-.01.03-.01.05-.01.02-.01.04-.01.08,0-.04,0-.09,0-.13.01-.05.01-.1.02-.14.03-2.42.58-4.11,2.57-5.02,3.95-.11-.07-.2-.13-.29-.2ZM145.71,75.46c-.66,1.12-2.17,3.28-4.42,4.06-.03.01-.05.02-.09.03-.12.03-.24.08-.37.1,0,.01-.01.01-.01.01h-.02c-.13.03-.26.07-.39.08-.05.01-.12.02-.19.03h-.1s-.08.01-.1.01c-.01,0-.02.01-.03,0-.16.02-.32.03-.47.03-.07,0-.13.01-.19,0-.1-.01-.2-.01-.32-.01-.11,0-.21-.01-.32-.02h-.02c-.25-.02-.5-.07-.76-.11t-.02-.01c-.19-.03-.36-.08-.53-.12-.19-.04-.36-.09-.54-.16-1.18-.36-2.2-.89-2.78-1.23.01-.02.02-.03.04-.06h0c.1-.19.22-.4.38-.64.26-.4.61-.86,1.03-1.33.08-.09.16-.19.25-.28.84-.89,1.95-1.72,3.3-2.04.87-.2,1.83-.2,2.89,0,.43.09.84.2,1.24.34.02.01.03.01.05.02.82.26,1.55.61,2.09.9t.01.01c.18.09.33.17.45.25-.02.04-.05.09-.09.15ZM145.57,74.32h.01s.07.03.1.06c-.04-.01-.07-.02-.11-.06ZM146.2,74.76c-.14-.11-.31-.22-.5-.36.2.1.37.2.53.29-.02.02-.02.04-.02.07ZM146.42,73.79h0c-.04.18-.08.33-.11.45-.19-.09-.41-.19-.65-.31-.19-.1-.38-.21-.6-.34-1.27-.74-2.88-1.94-3.73-3.65-.98-1.96-.77-4.18-.47-5.58.05-.26.11-.48.16-.67v-.04c1.24.56,3.82,1.95,4.98,4.3.89,1.77.8,3.74.56,5.13-.04.26-.09.5-.14.72Z" />
                <path class="cls-1"
                    d="M44.64,60.23c.07.09.14.16.23.23-.08.07-.16.14-.23.23-.07-.09-.14-.16-.23-.23.08-.07.16-.14.23-.23M44.65,64.97l.16.05c.26.08.43.31.43.58s-.17.5-.43.58l-.16.05-.16-.05c-.26-.08-.43-.31-.43-.58s.17-.5.43-.58l.16-.05M44.65,69.44l.16.05c.26.08.43.31.43.58s-.17.5-.43.58l-.16.05-.16-.05c-.26-.08-.43-.31-.43-.58s.17-.5.43-.58l.16-.05M44.64,76.64c.23.47.54.78.93.99-.42.22-.71.54-.93.99-.21-.45-.51-.77-.93-.99.39-.21.7-.52.93-.99M44.65,54.73c-.18,0-.33.15-.33.34v4.64c-.21.41-.56.58-1.21.66-.11.01-.11.17,0,.19.65.09,1.01.26,1.21.66v3.27c-.47.15-.82.58-.82,1.11s.35.96.82,1.11v2.26c-.47.15-.82.58-.82,1.11s.35.96.82,1.11v4.72c-.28,1.04-.83,1.41-2.04,1.58-.17.02-.17.28,0,.31,1.59.23,2.04.78,2.23,2.73,0,.09.07.13.13.13s.12-.04.13-.13c.19-1.95.64-2.5,2.23-2.73.17-.02.17-.28,0-.31-1.18-.17-1.74-.52-2.02-1.5v-4.8c.47-.15.82-.58.82-1.11s-.35-.96-.82-1.11v-2.26c.47-.15.82-.58.82-1.11s-.35-.96-.82-1.11v-3.3c.21-.38.56-.54,1.19-.63.11-.01.11-.17,0-.19-.63-.09-.98-.25-1.19-.63v-4.67c0-.19-.15-.34-.33-.34h0Z" />
                <path class="cls-1"
                    d="M251.51,70.75h0,0M251.51,75.7l.12.1s0,0,.01,0c0,0,0,0-.01,0l-.12.1-.12-.1s0,0-.01,0c0,0,0,0,.01,0l.12-.1M251.51,79.89l.22.09c.25.09.4.32.4.58s-.16.49-.4.58l-.22.09-.22-.09c-.25-.09-.4-.32-.4-.58s.16-.49.4-.58l.22-.09M251.51,84.79l.03.07c.14.29.34.49.59.62-.25.13-.45.33-.59.62l-.03.07v-.18l-.06-.09c-.13-.18-.28-.31-.47-.42.2-.11.35-.24.47-.42l.06-.09v-.18M251.51,90.08l.06.09c.1.15.21.26.36.36-.18.11-.31.25-.41.43-.1-.19-.24-.33-.41-.43.14-.09.26-.21.36-.36l.06-.09M251.51,70.41c-.19,0-.35.16-.35.35v4.79c-.16.14-.41.2-.8.24-.05,0-.05.06,0,.07.4.03.64.1.8.24v3.56c-.37.14-.63.49-.63.9s.26.76.63.9v3.41c-.23.32-.59.47-1.26.54-.11.01-.11.14,0,.15.67.07,1.03.22,1.26.54v3.87c-.18.28-.47.41-.97.48-.09.01-.09.14,0,.15.88.12,1.14.4,1.24,1.44,0,.04.04.07.07.07s.07-.02.07-.07c.11-1.04.36-1.32,1.24-1.44.09-.01.09-.14,0-.15-.5-.07-.78-.2-.97-.48v-3.72c.21-.44.59-.61,1.36-.7.11-.01.11-.14,0-.15-.77-.08-1.15-.26-1.36-.7v-3.26c.37-.14.63-.49.63-.9s-.26-.76-.63-.9v-3.56c.16-.14.41-.2.8-.24.05,0,.05-.06,0-.07-.4-.03-.64-.1-.8-.24v-4.79c0-.19-.16-.35-.35-.35h0Z" />
                <path class="cls-1"
                    d="M192.82,49.55c.1.19.24.33.42.42-.18.1-.32.23-.42.42-.1-.19-.24-.33-.42-.42.18-.1.32-.23.42-.42M192.82,53.05c.1.19.24.33.42.42-.18.1-.32.23-.42.42-.1-.19-.24-.33-.42-.42.18-.1.32-.23.42-.42M192.82,62.19c.28,1.49.89,2.14,2.37,2.44-1.46.29-2.08.9-2.37,2.39-.29-1.5-.9-2.1-2.37-2.39,1.47-.3,2.08-.95,2.36-2.44M192.82,46.06c-.09,0-.16.07-.16.16v2.98c-.16.52-.5.67-1.34.73-.05,0-.05.07,0,.08.84.06,1.18.21,1.34.73v1.97c-.16.52-.5.67-1.34.73-.05,0-.05.07,0,.08.84.07,1.18.21,1.34.73v7.1c-.26,2.4-.9,2.95-3.47,3.17-.14.01-.14.21,0,.22,2.78.24,3.31.84,3.53,3.77,0,.06.05.09.1.09s.09-.03.1-.09c.22-2.94.75-3.54,3.53-3.77.14-.01.14-.21,0-.22-2.57-.22-3.21-.77-3.47-3.17v-7.1c.16-.52.5-.67,1.34-.73.05,0,.05-.07,0-.08-.84-.07-1.18-.21-1.34-.73v-1.97c.16-.52.5-.67,1.34-.73.05,0,.05-.07,0-.08-.84-.06-1.18-.21-1.34-.73v-2.98c0-.09-.07-.16-.16-.16h0Z" />
            </svg>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class='bx bxs-dashboard'></i> <span>Dashboard</span></a></li>
                <li class="active"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="m21.45 11.11-3-1.5-2.68-1.34-.03-.03-1.34-2.68-1.5-3c-.34-.68-1.45-.68-1.79 0l-1.5 3-1.34 2.68-.03.03-2.68 1.34-3 1.5c-.34.17-.55.52-.55.89s.21.72.55.89l3 1.5 2.68 1.34.03.03 1.34 2.68 1.5 3c.17.34.52.55.89.55s.72-.21.89-.55l1.5-3 1.34-2.68.03-.03 2.68-1.34 3-1.5c.34-.17.55-.52.55-.89s-.21-.72-.55-.89ZM19.5 1.5l-.94 2.06-2.06.94 2.06.94.94 2.06.94-2.06 2.06-.94-2.06-.94z">
                            </path>
                        </svg> <span>Items/Catalog</span></a></li>
                <li><a href="customers.php"><i class='bx bxs-user-circle'></i> <span>Customers</span></a></li>
                <li><a href="orders.php"><i class='bx bxs-shopping-bags'></i> <span>Orders</span></a></li>
                <li><a href="designers.php"><i class='bx bxs-palette'></i> <span>Designers</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <h2>Catalog Management</h2>
            <div class="user-actions">
                <span>Admin</span>
                <a href="logout.php" class="logout"><i class='bx bx-log-out-circle'></i> Log Out</a>
            </div>
        </header>

        <?php if ($success_msg): ?><div class="alert alert-success"><i class='bx bx-check-circle'></i>
                <?php echo $success_msg; ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-error"><i class='bx bx-x-circle'></i> <?php echo $error_msg; ?>
            </div><?php endif; ?>

        <div class="catalog-container">
            <div class="filters-section">
                <form method="GET"
                    style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                    <div class="filter-group"><label>Search</label><input type="text" name="search"
                            placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn-filter"><i class='bx bx-search'></i> Filter</button>
                    <a href="catalog.php" class="btn-filter"
                        style="background:#f3f4f6; color:#333; text-decoration:none; text-align:center;">Reset</a>
                </form>
                <button class="btn-add-product" onclick="openModal('add')"><i class='bx bx-plus'></i> Add
                    Product</button>
            </div>

            <div class="products-table">
                <div class="products-list">
                    <div class="product-row" style="background: #f9fafb; font-weight: 600;">
                        <div>Image</div>
                        <div>Product Name</div>
                        <div>Category/Tags</div>
                        <div>Price</div>
                        <div>Stock</div>
                        <div>Material</div>
                        <div>Actions</div>
                    </div>
                    <?php foreach ($items as $item): ?>
                        <div class="product-row">
                            <div class="product-image">
                                <?php if (!empty($item['ITEM_IMAGE'])): ?>
                                    <?php
                                    // 1. Remove any leading slash so we have a clean path (e.g. "images/products/img.jpg")
                                    $cleanPath = ltrim($item['ITEM_IMAGE'], '/');

                                    // 2. Add "../" to tell the browser to go up one folder from 'admin' to 'tink'
                                    $displayUrl = '../' . $cleanPath;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($displayUrl); ?>" alt="Product"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                                <?php else: ?>
                                    <div
                                        style="width: 50px; height: 50px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                        <i class='bx bx-image' style="font-size: 1.5rem; color: #cbd5e1;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-name">
                                <?php echo htmlspecialchars($item['ITEM_NAME']); ?>
                                <div style="font-size:0.75rem; color:#666;">
                                    <?php echo htmlspecialchars($item['DESIGNER_NAME']); ?>
                                </div>
                                <?php if ($item['variant_count'] > 1): ?>
                                    <span class="variant-badge">
                                        <?php echo $item['variant_count']; ?> Variants
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="category-badge"><?php echo htmlspecialchars($item['ITEM_CATEGORY']); ?></span>
                                <?php if ($item['PARENT_ID']): ?>
                                    <div style="margin-top:5px; font-size:0.7rem; color:#666;"><i class='bx bx-link'></i>
                                        Linked: <?php echo $item['PARENT_ID']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div>RM <?php echo number_format($item['ITEM_PRICE'], 2); ?></div>
                            <div>
                                <span
                                    class="stock-status <?php echo $item['ITEM_STOCK'] > 15 ? 'stock-high' : 'stock-low'; ?>"><?php echo $item['ITEM_STOCK']; ?></span>
                            </div>
                            <div><?php echo htmlspecialchars($item['ITEM_MATERIAL']); ?></div>
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit"
                                    onclick="openModal('edit', <?php echo $item['ITEM_ID']; ?>)"><i
                                        class='bx bx-edit'></i></button>
                                <button class="btn-icon btn-delete"
                                    onclick="deleteProduct(<?php echo $item['ITEM_ID']; ?>)"><i
                                        class='bx bx-trash'></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort_by; ?>"
                        class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Product Details</h2>
                <button class="btn-close" onclick="closeModal()"><i class='bx bx-x'></i></button>
            </div>

            <div class="modal-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="existing_gallery_images" id="existingGalleryImages" value="">

                    <div class="form-section">
                        <h3 class="form-section-title">Base Product Information</h3>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Product Name</label>
                            <input type="text" id="baseName" onkeyup="syncName()" placeholder="e.g. Floral Signet Ring"
                                required>
                        </div>

                        <div class="form-grid-2">
                            <div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="item_category" id="itemCategory" required>
                                        <option value="">Select category...</option>
                                        <option value="Necklaces">Necklaces</option>
                                        <option value="Earrings">Earrings</option>
                                        <option value="Bracelets">Bracelets</option>
                                        <option value="Rings">Rings</option>
                                        <option value="Charms">Charms</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Designer</label>
                                    <select name="designer_id" id="designerId" required>
                                        <option value="">Select designer...</option>
                                        <?php foreach ($designers_list as $d): ?>
                                            <option value="<?php echo $d['DESIGNER_ID']; ?>">
                                                <?php echo htmlspecialchars($d['DESIGNER_NAME']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="parentIdContainer">
                                    <label for="parentId">Link to Parent Product (Charms)</label>
                                    <select name="parent_id" id="parentId">
                                        <option value="">-- No Parent (New Base) --</option>
                                        <?php foreach ($parent_items as $p): ?>
                                            <option value="<?php echo $p['ITEM_ID']; ?>">
                                                <?php echo htmlspecialchars($p['ITEM_NAME']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="item_description" id="itemDescription" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Product Gallery</h3>
                        <div class="gallery-upload-section">
                            <div class="gallery-upload-area" onclick="document.getElementById('galleryUpload').click()">
                                <input type="file" id="galleryUpload" name="base_item_images[]" multiple
                                    accept="image/*" onchange="handleGalleryUpload(event)">
                                <div class="gallery-upload-text">
                                    <i class='bx bx-cloud-upload'></i>
                                    <strong>Click to upload multiple images</strong>
                                </div>
                            </div>
                            <div class="gallery-preview" id="galleryPreview"></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Tags & Attributes</h3>
                        <div class="tags-scroll-container">
                            <?php foreach ($tag_categories as $category => $tags): ?>
                                <div class="tag-category-group" id="cat-group-<?php echo $category; ?>">
                                    <div class="tag-category-title"><?php echo $category; ?></div>
                                    <div class="checkbox-grid">
                                        <?php foreach ($tags as $stag): ?>
                                            <div class="checkbox-item">
                                                <label>
                                                    <input type="checkbox" name="item_tags_select[]"
                                                        value="<?php echo $stag; ?>" class="tag-checkbox">
                                                    <?php echo $stag; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="tag-input-group">
                            <select id="newTagCategory">
                                <?php foreach (array_keys($tag_categories) as $cat) echo "<option value='$cat'>$cat</option>"; ?>
                            </select>
                            <input type="text" id="newTagName" placeholder="Add new tag name..." />
                            <button type="button" class="tag-add-btn" onclick="addNewTag()"><i class='bx bx-plus'></i>
                                Add</button>
                        </div>

                        <label style="display:flex; align-items:center; gap:8px; margin-top:12px; cursor:pointer;">
                            <input type="checkbox" name="is_engravable" id="isEngravable" value="1"
                                style="width:16px; height:16px; accent-color:#3b82f6;">
                            <span style="font-size:0.875rem; color:#334155;">Allow Engraving</span>
                        </label>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Product Variants</h3>
                        <div class="variant-container">
                            <div class="variant-header">
                                <div>Name</div>
                                <div>Material</div>
                                <div>Price</div>
                                <div>Stock</div>
                                <div style="text-align:center;">Image</div>
                                <div></div>
                            </div>
                            <div id="variantsContainer" class="variant-list"></div>
                        </div>
                        <button type="button" class="btn-add-variant" onclick="addVariantRow()"><i
                                class='bx bx-plus-circle'></i> Add Variant</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" form="productForm" class="btn-submit">Save Product(s)</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        const modal = document.getElementById('productModal');
        const variantsContainer = document.getElementById('variantsContainer');
        let galleryFiles = [];
        let existingGallery = [];

        $(document).ready(function() {
            $('#parentId').select2({
                placeholder: '-- No Parent (New Base) --',
                allowClear: true,
                width: '100%'
            });
            $('#itemCategory').change(function() {
                if ($(this).val() === 'Charms') $('#parentIdContainer').slideDown();
                else {
                    $('#parentIdContainer').slideUp();
                    $('#parentId').val(null).trigger('change');
                }
            });
        });

        function syncName() {
            const base = document.getElementById('baseName').value;
            document.querySelectorAll('input[name="var_name[]"]').forEach(input => {
                input.value = base;
            });
        }

        function addNewTag() {
            const cat = document.getElementById('newTagCategory').value;
            const name = document.getElementById('newTagName').value.trim();
            if (!name) return;
            const existingInputs = document.querySelectorAll(`input[name="item_tags_select[]"][value="${name}"]`);
            if (existingInputs.length > 0) {
                existingInputs[0].checked = true;
                document.getElementById('newTagName').value = '';
                return;
            }
            const wrapper = document.querySelector(`#cat-group-${cat} .checkbox-grid`);
            const div = document.createElement('div');
            div.className = 'checkbox-item';
            div.innerHTML =
                `<label><input type="checkbox" name="item_tags_select[]" value="${name}" class="tag-checkbox" checked> ${name}</label>`;
            wrapper.appendChild(div);
            document.getElementById('newTagName').value = '';
        }

        function handleGalleryUpload(e) {
            const files = Array.from(e.target.files);
            galleryFiles = galleryFiles.concat(files);
            updateGalleryPreview();
        }

        function updateGalleryPreview() {
            const preview = document.getElementById('galleryPreview');
            preview.innerHTML = '';
            existingGallery.forEach((url, index) => {
                const item = document.createElement('div');
                item.className = 'gallery-item';
                // FIXED: Handle both path formats for preview
                const displayPath = '../' + url.replace(/^\//, '');
                item.onclick = (e) => {
                    if (!e.target.closest('.gallery-item-remove')) openLightbox(displayPath);
                };
                item.innerHTML =
                    `<img src="${displayPath}"><button type="button" class="gallery-item-remove" onclick="removeExistingImage(${index})"><i class='bx bx-x'></i></button>`;
                preview.appendChild(item);
            });
            galleryFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const item = document.createElement('div');
                    item.className = 'gallery-item';
                    item.onclick = (ev) => {
                        if (!ev.target.closest('.gallery-item-remove')) openLightbox(e.target.result);
                    };
                    item.innerHTML =
                        `<img src="${e.target.result}"><button type="button" class="gallery-item-remove" onclick="removeNewImage(${index})"><i class='bx bx-x'></i></button>`;
                    preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
            document.getElementById('existingGalleryImages').value = JSON.stringify(existingGallery);
        }

        function removeExistingImage(i) {
            existingGallery.splice(i, 1);
            updateGalleryPreview();
        }

        function removeNewImage(i) {
            galleryFiles.splice(i, 1);
            updateGalleryPreview();
        }

        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('imageLightbox').style.display = 'flex';
        }

        document.getElementById('productForm').addEventListener('submit', function(e) {
            const dt = new DataTransfer();
            galleryFiles.forEach(f => dt.items.add(f));
            document.getElementById('galleryUpload').files = dt.files;
        });

        function openModal(mode, id = null) {
            document.getElementById('productForm').reset();
            variantsContainer.innerHTML = '';
            galleryFiles = [];
            existingGallery = [];
            updateGalleryPreview();
            document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = false);
            $('#parentId').val(null).trigger('change');
            $('#parentIdContainer').hide();

            if (mode === 'edit') {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                fetch(`api/get-product.php?item_id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        const items = Array.isArray(data) ? data : [data];
                        const baseItem = items[0];
                        document.getElementById('baseName').value = baseItem.ITEM_NAME;
                        document.getElementById('itemCategory').value = baseItem.ITEM_CATEGORY;
                        $('#itemCategory').trigger('change');
                        document.getElementById('designerId').value = baseItem.DESIGNER_ID;
                        document.getElementById('itemDescription').value = baseItem.ITEM_DESCRIPTION;
                        document.getElementById('isEngravable').checked = (baseItem.IS_ENGRAVABLE == 1);
                        if (baseItem.PARENT_ID) $('#parentId').val(baseItem.PARENT_ID).trigger('change');

                        if (baseItem.ITEM_TAGS) {
                            const tags = baseItem.ITEM_TAGS.split(',').map(s => s.trim());
                            tags.forEach(t => {
                                let found = false;
                                document.querySelectorAll('.tag-checkbox').forEach(cb => {
                                    if (cb.value === t) {
                                        cb.checked = true;
                                        found = true;
                                    }
                                });
                                if (!found) {
                                    const wrapper = document.querySelector(
                                        `#cat-group-Features .checkbox-grid`);
                                    const div = document.createElement('div');
                                    div.className = 'checkbox-item';
                                    div.innerHTML =
                                        `<label><input type="checkbox" name="item_tags_select[]" value="${t}" class="tag-checkbox" checked> ${t}</label>`;
                                    wrapper.appendChild(div);
                                }
                            });
                        }
                        if (baseItem.GALLERY_IMAGES) {
                            try {
                                existingGallery = JSON.parse(baseItem.GALLERY_IMAGES);
                                updateGalleryPreview();
                            } catch (e) {}
                        }
                        items.forEach(item => {
                            addVariantRow(item);
                        });
                    });
            } else {
                document.getElementById('modalTitle').textContent = 'Add New Product';
                addVariantRow();
            }
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        function addVariantRow(data = null) {
            const row = document.createElement('div');
            row.className = 'variant-row';
            const name = data ? data.ITEM_NAME : document.getElementById('baseName').value;
            const mat = data ? data.ITEM_MATERIAL : '';
            const price = data ? data.ITEM_PRICE : '';
            const stock = data ? data.ITEM_STOCK : '';
            const id = data ? data.ITEM_ID : '';

            row.innerHTML = `
                <input type="hidden" name="var_id[]" value="${id}">
                <input type="text" name="var_name[]" value="${name}" placeholder="Name" required>
                <input type="text" name="var_material[]" value="${mat}" placeholder="Material" required>
                <input type="number" step="0.01" name="var_price[]" value="${price}" placeholder="0.00" required>
                <input type="number" name="var_stock[]" value="${stock}" placeholder="0" required>
                <div style="text-align:center"><label class="row-upload-btn ${data && data.ITEM_IMAGE ? 'has-file' : ''}"><i class='bx bx-upload'></i><input type="file" name="var_image[]" onchange="this.parentElement.classList.add('has-file')"></label></div>
                <button type="button" class="btn-remove-variant" onclick="this.parentElement.remove()"><i class='bx bx-x'></i></button>
            `;
            variantsContainer.appendChild(row);
        }

        function deleteProduct(id) {
            if (confirm('Delete this product (and all variants)?')) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML =
                    `<input type="hidden" name="action" value="delete_product"><input type="hidden" name="item_id" value="${id}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }
    </script>
</body>

</html>