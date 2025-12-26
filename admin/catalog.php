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

            // Parent ID Logic (Only for Charms)
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

            // 3. GROUPING LOGIC (Preserve ID on Edit)
            if (empty($existing_parent_id) && !empty($ids[0])) {
                $check_stmt = $pdo->prepare("SELECT PARENT_ID FROM ITEM WHERE ITEM_ID = ?");
                $check_stmt->execute([intval($ids[0])]);
                $db_parent = $check_stmt->fetchColumn();
                if ($db_parent) {
                    $existing_parent_id = $db_parent;
                }
            }

            // Generate New Group ID if needed
            $final_parent_id = $existing_parent_id;
            if (empty($final_parent_id) && count($names) > 1) {
                $final_parent_id = rand(10000, 99999);
            }
            if (count($names) === 1 && empty($existing_parent_id)) {
                $final_parent_id = null;
            }

            // 4. GALLERY IMAGES
            $final_gallery = [];
            if (!empty($_POST['existing_gallery_images'])) {
                $final_gallery = json_decode($_POST['existing_gallery_images'], true) ?? [];
            }

            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!empty($_FILES['base_item_images']['name'][0])) {
                for ($i = 0; $i < count($_FILES['base_item_images']['name']); $i++) {
                    if (!empty($_FILES['base_item_images']['name'][$i])) {
                        $ext = strtolower(pathinfo($_FILES['base_item_images']['name'][$i], PATHINFO_EXTENSION));
                        $filename = 'base_' . time() . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($_FILES['base_item_images']['tmp_name'][$i], $upload_dir . $filename)) {
                            $final_gallery[] = '/images/products/' . $filename;
                        }
                    }
                }
            }
            $gallery_json = !empty($final_gallery) ? json_encode(array_values($final_gallery)) : null;

            // 5. SAVE / UPDATE VARIANTS
            $saved_ids = []; // Keep track of IDs we saved to detect deletions

            for ($i = 0; $i < count($names); $i++) {
                $v_name = trim($names[$i]);
                $v_mat = trim($materials[$i]);
                $v_price = floatval($prices[$i]);
                $v_stock = intval($stocks[$i]);
                $v_id = !empty($ids[$i]) ? intval($ids[$i]) : null;

                $image_path = null;
                if (!empty($_FILES['var_image']['name'][$i])) {
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['var_image']['name'][$i], PATHINFO_EXTENSION));
                    $filename = 'var_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['var_image']['tmp_name'][$i], $upload_dir . $filename)) {
                        $image_path = '/images/products/' . $filename;
                    }
                }

                if ($v_id) {
                    $sql = "UPDATE ITEM SET DESIGNER_ID=?, ITEM_CATEGORY=?, ITEM_NAME=?, ITEM_DESCRIPTION=?, ITEM_MATERIAL=?, ITEM_PRICE=?, ITEM_STOCK=?, ITEM_TAGS=?, PARENT_ID=?, IS_ENGRAVABLE=?, GALLERY_IMAGES=?";
                    $params = [$designer_id, $item_cat, $v_name, $item_desc, $v_mat, $v_price, $v_stock, $item_tags, $final_parent_id, $is_engravable, $gallery_json];

                    if ($image_path) {
                        $sql .= ", ITEM_IMAGE=?";
                        $params[] = $image_path;
                    }

                    $sql .= " WHERE ITEM_ID=?";
                    $params[] = $v_id;
                    $pdo->prepare($sql)->execute($params);
                    $saved_ids[] = $v_id;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO ITEM (DESIGNER_ID, ITEM_CATEGORY, ITEM_NAME, ITEM_DESCRIPTION, ITEM_MATERIAL, ITEM_PRICE, ITEM_STOCK, ITEM_IMAGE, ITEM_TAGS, PARENT_ID, IS_ENGRAVABLE, GALLERY_IMAGES) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$designer_id, $item_cat, $v_name, $item_desc, $v_mat, $v_price, $v_stock, $image_path, $item_tags, $final_parent_id, $is_engravable, $gallery_json]);
                    $saved_ids[] = $pdo->lastInsertId();
                }
            }

            // 6. PRUNE DELETED VARIANTS (New Fix)
            // If we have a group (Parent ID), check for items in DB that were NOT in the submitted form
            if ($final_parent_id) {
                $stmt = $pdo->prepare("SELECT ITEM_ID FROM ITEM WHERE PARENT_ID = ?");
                $stmt->execute([$final_parent_id]);
                $db_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Find IDs in DB that are missing from the saved list
                $ids_to_remove = array_diff($db_ids, $saved_ids);

                if (!empty($ids_to_remove)) {
                    $in_remove = implode(',', array_fill(0, count($ids_to_remove), '?'));
                    // Delete dependencies first
                    $pdo->prepare("DELETE FROM CARTITEM WHERE ITEM_ID IN ($in_remove)")->execute(array_values($ids_to_remove));
                    $pdo->prepare("DELETE FROM ITEM WHERE ITEM_ID IN ($in_remove)")->execute(array_values($ids_to_remove));
                }
            }

            $success_msg = "Product(s) saved successfully!";
        }

        // --- FIXED DELETE LOGIC ---
        if ($action === 'delete_product') {
            $target_id = intval($_POST['item_id']);

            // 1. Check if this item is part of a group
            $stmt = $pdo->prepare("SELECT PARENT_ID FROM ITEM WHERE ITEM_ID = ?");
            $stmt->execute([$target_id]);
            $parent_id = $stmt->fetchColumn();

            // 2. Identify ALL IDs to delete (The specific item + any siblings if it's a group)
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

                // 3. FORCE DELETE DEPENDENCIES (Cart, Reviews, Gallery)
                $pdo->prepare("DELETE FROM CARTITEM WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM REVIEW WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM ITEM_GALLERY WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);
                $pdo->prepare("DELETE FROM ITEMCHARM WHERE ITEM_ID IN ($in_query)")->execute($ids_to_delete);

                // 4. Finally Delete Items
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

// 1. COUNT GROUPS
$total_items = $pdo->prepare("SELECT COUNT(DISTINCT COALESCE(PARENT_ID, ITEM_ID)) FROM ITEM i WHERE $where_sql");
$total_items->execute($params);
$total_items = $total_items->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// 2. FETCH GROUPED ITEMS
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
        /* ... (KEEP YOUR EXISTING CSS FROM PREVIOUS STEPS) ... */
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

        /* Include full CSS block here or link to external file */
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
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 288 149.67"
                style="width: 100%; height: auto; max-width: 150px;">
                <path
                    d="M108.85,55.5h-.66c-4.05-14.45-12.56-14.49-23.14-14.49v66.68c0,5.53,5.29,9.31,10.02,9.31v.93h-36.91v-.93c4.73,0,10.02-3.78,10.02-9.31V41.01c-10.57,0-19.15.04-23.19,14.49h-.68l.24-15.54h64.13l.18,15.54Z"
                    fill="#000" />
                <path
                    d="M140.41,111.44h0c-3.72,2.98-10.76,8.06-16.25,8.06-6.75,0-11.59-5.28-11.81-11.28v-24.56c-.21-3.4-1.68-6.97-6.83-6.97v-.82c9.34-1.17,20.13-6.58,20.13-6.58h.68v36.26c.28,4.99,2.5,8.07,6.49,8.07,3.09,0,4.82-1.22,6.94-3.11h0"
                    fill="#000" />
            </svg>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class='bx bxs-dashboard'></i> <span>Dashboard</span></a></li>
                <li class="active"><a href="#"><i class='bx bxs-component'></i> <span>Items/Catalog</span></a></li>
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
                                <?php if ($item['ITEM_IMAGE']): ?>
                                    <img src="<?php echo htmlspecialchars($item['ITEM_IMAGE']); ?>" alt="Img">
                                <?php else: ?>
                                    <i class='bx bx-image-alt' style="font-size: 2rem; color: #ccc;"></i>
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
                item.onclick = (e) => {
                    if (!e.target.closest('.gallery-item-remove')) openLightbox(url);
                };
                item.innerHTML =
                    `<img src="${url}"><button type="button" class="gallery-item-remove" onclick="removeExistingImage(${index})"><i class='bx bx-x'></i></button>`;
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

                // FETCH ALL VARIANTS NOW
                fetch(`api/get-product.php?item_id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        // Normalize data to array (even if single item)
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

                        // Add ALL variants to form
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