<?php
session_start();
require_once '../config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error_msg = '';

// --- FETCH ALL ITEMS (For Dropdowns) ---
$all_items = $pdo->query("SELECT ITEM_ID, ITEM_NAME, ITEM_CATEGORY FROM ITEM ORDER BY ITEM_NAME ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- HANDLE POST REQUESTS (Save/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_charm') {
        try {
            $id = !empty($_POST['charm_id']) ? intval($_POST['charm_id']) : null;
            $name = trim($_POST['charm_name']);
            $type = $_POST['charm_type'];
            $material = $_POST['charm_material'];
            $price = floatval($_POST['charm_price']);
            $cat = $_POST['charm_compatible_cat'];
            $active = isset($_POST['charm_active']) ? 1 : 0;

            $image_path = $_POST['existing_image'] ?? null;
            if (!empty($_FILES['charm_image']['name'])) {
                $upload_dir = dirname(__DIR__) . '/images/charms/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['charm_image']['name'], PATHINFO_EXTENSION));
                $filename = 'charm_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['charm_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = '/images/charms/' . $filename;
                }
            }

            if ($id) {
                $sql = "UPDATE CHARM SET CHARM_NAME=?, CHARM_TYPE=?, CHARM_MATERIAL=?, CHARM_PRICE=?, CHARM_COMPATIBLE_CAT=?, CHARM_ACTIVE=?";
                $params = [$name, $type, $material, $price, $cat, $active];
                if ($image_path) {
                    $sql .= ", CHARM_IMAGE=?";
                    $params[] = $image_path;
                }
                $sql .= " WHERE CHARM_ID=?";
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
            } else {
                $stmt = $pdo->prepare("INSERT INTO CHARM (CHARM_NAME, CHARM_TYPE, CHARM_MATERIAL, CHARM_PRICE, CHARM_COMPATIBLE_CAT, CHARM_ACTIVE, CHARM_IMAGE) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $material, $price, $cat, $active, $image_path]);
                $id = $pdo->lastInsertId();
            }

            $pdo->prepare("DELETE FROM ITEMCHARM WHERE CHARM_ID = ?")->execute([$id]);
            if (!empty($_POST['linked_items'])) {
                $ins = $pdo->prepare("INSERT INTO ITEMCHARM (ITEM_ID, CHARM_ID) VALUES (?, ?)");
                foreach ($_POST['linked_items'] as $iid) $ins->execute([$iid, $id]);
            }
            $success_msg = "Charm saved successfully!";
        } catch (Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'delete_charm') {
        $tid = intval($_POST['charm_id']);

        // 1. Remove from "Linker" table (Admin settings)
        $pdo->prepare("DELETE FROM ITEMCHARM WHERE CHARM_ID = ?")->execute([$tid]);

        // 2. Remove from Customer Carts (The Missing Step)
        $pdo->prepare("DELETE FROM CARTITEM_CHARM WHERE CHARM_ID = ?")->execute([$tid]);

        // 3. Now it is safe to delete the Charm
        $pdo->prepare("DELETE FROM CHARM WHERE CHARM_ID = ?")->execute([$tid]);

        $success_msg = "Charm deleted!";
    }
}

// --- AJAX GET (Edit Details) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_charm_details') {
    $stmt = $pdo->prepare("SELECT * FROM CHARM WHERE CHARM_ID = ?");
    $stmt->execute([$_GET['id']]);
    $charm = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmtL = $pdo->prepare("SELECT ITEM_ID FROM ITEMCHARM WHERE CHARM_ID = ?");
    $stmtL->execute([$_GET['id']]);
    $links = $stmtL->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['charm' => $charm, 'linked_items' => $links]);
    exit;
}

// --- BUILD SEARCH QUERY ---
$where_clauses = ["1=1"];
$params = [];

// 1. Search by Name
if (!empty($_GET['search'])) {
    $where_clauses[] = "c.CHARM_NAME LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

// 2. Filter by Linked Item
if (!empty($_GET['filter_item'])) {
    // Join logic: We need charms that exist in ITEMCHARM for this specific item_id
    $where_clauses[] = "c.CHARM_ID IN (SELECT ic.CHARM_ID FROM ITEMCHARM ic WHERE ic.ITEM_ID = ?)";
    $params[] = $_GET['filter_item'];
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM ITEMCHARM ic WHERE ic.CHARM_ID = c.CHARM_ID) as link_count 
        FROM CHARM c 
        WHERE $where_sql 
        ORDER BY c.CHARM_ID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$charms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charms Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        /* --- GENERAL --- */
        .select2-container {
            width: 100% !important;
        }

        .select2-selection {
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
        }

        /* --- FILTER SECTION STYLES --- */
        .filters-section {
            display: flex;
            gap: 15px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-group {
            display: flex;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0 10px;
            background: #fff;
            flex: 1;
            min-width: 200px;
        }

        .search-group input {
            border: none;
            outline: none;
            padding: 8px;
            width: 100%;
            font-size: 0.9rem;
        }

        .filter-group {
            width: 250px;
            /* Fixed width for filter dropdown */
        }

        .btn-filter {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-filter:hover {
            background: #e2e8f0;
        }

        /* --- MODAL STYLES --- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.2s ease-out;
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
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-radius: 0 0 12px 12px;
        }

        /* --- FORM GRID --- */
        .top-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .input-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        /* --- IMAGE UPLOAD --- */
        .image-preview-box {
            width: 140px;
            height: 140px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
        }

        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .select2-dropdown {
            z-index: 99999 !important;
        }

        .charm-img-thumb {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
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
                <li>
                    <a href="dashboard.php"><i class='bx bxs-dashboard'></i> <span>Dashboard</span></a>
                </li>
                <li>
                    <a href="catalog.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="m21.45 11.11-3-1.5-2.68-1.34-.03-.03-1.34-2.68-1.5-3c-.34-.68-1.45-.68-1.79 0l-1.5 3-1.34 2.68-.03.03-2.68 1.34-3 1.5c-.34.17-.55.52-.55.89s.21.72.55.89l3 1.5 2.68 1.34.03.03 1.34 2.68 1.5 3c.17.34.52.55.89.55s.72-.21.89-.55l1.5-3 1.34-2.68.03-.03 2.68-1.34 3-1.5c.34-.17.55-.52.55-.89s-.21-.72-.55-.89ZM19.5 1.5l-.94 2.06-2.06.94 2.06.94.94 2.06.94-2.06 2.06-.94-2.06-.94z">
                            </path>
                        </svg> <span>Items/Catalog</span></a>
                </li>
                <li class="active"><a href="charms.php"><i class='bx bxs-magic-wand'></i> <span>Charms</span></a></li>

                <li>
                    <a href="customers.php"><i class='bx bxs-user-circle'></i> <span>Customers</span></a>
                </li>
                <li>
                    <a href="orders.php"><i class='bx bxs-shopping-bags'></i> <span>Orders</span></a>
                </li>
                <li>
                    <a href="designers.php"><i class='bx bxs-palette'></i> <span>Designers</span></a>
                </li>
            </ul>
        </nav>
    </aside>



    <main class="main-content">
        <header>
            <h2>Charms Management</h2>
            <div class="user-actions">
                <span>Admin</span>
                <a href="logout.php" class="logout"><i class='bx bx-log-out-circle'></i> Log Out</a>
            </div>
        </header>

        <?php if ($success_msg): ?><div class="alert alert-success"><i class='bx bx-check-circle'></i>
                <?php echo $success_msg; ?></div><?php endif; ?>

        <div class="catalog-container">
            <form method="GET" class="filters-section">
                <div class="search-group">
                    <i class='bx bx-search' style="color:#94a3b8; font-size:1.2rem;"></i>
                    <input type="text" name="search" placeholder="Search charms..."
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <select name="filter_item" id="mainFilterSelect">
                        <option value="">Filter by Linked Item...</option>
                        <?php foreach ($all_items as $item): ?>
                            <option value="<?php echo $item['ITEM_ID']; ?>"
                                <?php echo (isset($_GET['filter_item']) && $_GET['filter_item'] == $item['ITEM_ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['ITEM_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-filter">Apply Filters</button>

                <?php if (!empty($_GET['search']) || !empty($_GET['filter_item'])): ?>
                    <a href="charms.php" style="color:#ef4444; font-size:0.9rem; text-decoration:underline;">Clear</a>
                <?php endif; ?>

                <div style="flex:1;"></div>
                <button type="button" class="btn-add-product" onclick="openModal('add')"
                    style="background:#f97316; border:none; color:white; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600;"><i
                        class='bx bx-plus'></i> Add Charm</button>
            </form>

            <div class="products-table">
                <div class="products-list">
                    <div class="product-row" style="background: #f9fafb; font-weight: 600; padding: 12px 15px;">
                        <div style="width: 60px;">Image</div>
                        <div style="flex:2">Charm Name</div>
                        <div style="flex:1">Type</div>
                        <div style="flex:1">Compat.</div>
                        <div style="flex:1">Price</div>
                        <div style="width: 100px; text-align:right;">Actions</div>
                    </div>

                    <?php foreach ($charms as $charm): ?>
                        <div class="product-row"
                            style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 15px;">
                            <div style="width: 60px;">
                                <?php if ($charm['CHARM_IMAGE']): ?>
                                    <img src="..<?php echo $charm['CHARM_IMAGE']; ?>" class="charm-img-thumb" alt="icon">
                                <?php else: ?>
                                    <div class="charm-img-thumb"
                                        style="display:flex;align-items:center;justify-content:center;background:#f1f5f9;"><i
                                            class='bx bx-star' style="color:#cbd5e1;"></i></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex:2">
                                <div style="font-weight:600; color:#1e293b;">
                                    <?php echo htmlspecialchars($charm['CHARM_NAME']); ?></div>
                                <?php if (!$charm['CHARM_ACTIVE']): ?>
                                    <span
                                        style="font-size:0.7rem; background:#fee2e2; color:#ef4444; padding:2px 6px; border-radius:4px;">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1; font-size:0.9rem; color:#64748b;">
                                <?php echo htmlspecialchars($charm['CHARM_TYPE']); ?></div>
                            <div style="flex:1">
                                <span class="category-badge"
                                    style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><?php echo htmlspecialchars($charm['CHARM_COMPATIBLE_CAT']); ?></span>
                                <?php if ($charm['link_count'] > 0): ?>
                                    <div style="font-size:0.75rem; color:#3b82f6; margin-top:4px;">
                                        <i class='bx bx-link'></i> <?php echo $charm['link_count']; ?> Linked Items
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1; font-weight:500;">RM <?php echo number_format($charm['CHARM_PRICE'], 2); ?>
                            </div>
                            <div style="width: 100px; text-align:right;">
                                <button class="btn-icon btn-edit"
                                    style="color:#3b82f6; background:none; border:none; cursor:pointer; font-size:1.2rem;"
                                    onclick="openModal('edit', <?php echo $charm['CHARM_ID']; ?>)"><i
                                        class='bx bx-edit'></i></button>
                                <button class="btn-icon btn-delete"
                                    style="color:#ef4444; background:none; border:none; cursor:pointer; font-size:1.2rem;"
                                    onclick="deleteCharm(<?php echo $charm['CHARM_ID']; ?>)"><i
                                        class='bx bx-trash'></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($charms)): ?>
                        <div style="padding:40px; text-align:center; color:#94a3b8;">No charms found matching your search.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="charmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" style="margin:0; font-size:1.25rem;">Add New Charm</h2>
                <button class="btn-close" style="background:none; border:none; font-size:1.5rem; cursor:pointer;"
                    onclick="closeModal()"><i class='bx bx-x'></i></button>
            </div>

            <div class="modal-body">
                <form id="charmForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_charm">
                    <input type="hidden" name="charm_id" id="charmId">
                    <input type="hidden" name="existing_image" id="existingImage">

                    <div class="top-grid">
                        <div>
                            <label class="form-group" style="margin-bottom:8px;">Charm Icon</label>
                            <label class="image-preview-box"
                                onclick="document.getElementById('charmImageUpload').click()">
                                <img id="previewImg" src="" style="display:none;">
                                <div id="uploadPlaceholder" class="upload-hint">
                                    <i class='bx bx-cloud-upload' style="font-size:2rem;color:#cbd5e1;"></i>
                                    <br><span style="font-size:0.7rem;color:#94a3b8;">Upload</span>
                                </div>
                            </label>
                            <input type="file" name="charm_image" id="charmImageUpload" style="display:none;"
                                onchange="previewFile(this)">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div class="form-group">
                                <label>Charm Name</label>
                                <input type="text" name="charm_name" id="charmName" required
                                    placeholder="e.g. Golden Star">
                            </div>

                            <div class="input-grid-2">
                                <div class="form-group">
                                    <label>Price (RM)</label>
                                    <input type="number" step="0.01" name="charm_price" id="charmPrice" required
                                        placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Type / Shape</label>
                                    <input type="text" name="charm_type" id="charmType" required
                                        placeholder="e.g. Heart">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Material</label>
                                <input type="text" name="charm_material" id="charmMaterial" required
                                    placeholder="e.g. 925 Sterling Silver">
                            </div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9; margin:0 0 25px 0;"></div>

                    <h3
                        style="font-size:0.85rem; font-weight:700; color:#94a3b8; margin-bottom:20px; text-transform:uppercase;">
                        Compatibility Settings</h3>

                    <div class="input-grid-2">
                        <div class="form-group">
                            <label>Category Filter</label>
                            <select name="charm_compatible_cat" id="charmCat" required>
                                <option value="Bracelets">Bracelets</option>
                                <option value="Necklaces">Necklaces</option>
                                <option value="All">All Categories</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <div
                                style="display:flex; align-items:center; gap:10px; height:42px; border:1px solid #e2e8f0; padding:0 12px; border-radius:6px; background:#fff;">
                                <input type="checkbox" name="charm_active" id="charmActive" value="1" checked
                                    style="width:20px; height:20px; margin:0;">
                                <label for="charmActive" style="margin:0; cursor:pointer; color:#1e293b;">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:10px;">
                        <label>Link to Specific Item</label>
                        <select name="linked_items[]" id="linkedItems" multiple="multiple">
                            <?php foreach ($all_items as $item): ?>
                                <option value="<?php echo $item['ITEM_ID']; ?>">
                                    <?php echo htmlspecialchars($item['ITEM_NAME']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel"
                    style="padding:10px 20px; border:1px solid #e2e8f0; background:#fff; border-radius:6px; cursor:pointer;"
                    onclick="closeModal()">Cancel</button>
                <button type="submit" form="charmForm" class="btn-submit"
                    style="padding:10px 20px; background:#f97316; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Save
                    Charm</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // 1. Initialize Main Page Filter with Select2
            $('#mainFilterSelect').select2({
                placeholder: "Filter by Linked Item...",
                allowClear: true,
                width: '100%'
            });

            // 2. Initialize Modal Linker with Select2
            $('#linkedItems').select2({
                placeholder: "Search items...",
                allowClear: true,
                dropdownParent: $('#charmModal')
            });
        });

        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('previewImg').style.display = 'block';
                    document.getElementById('uploadPlaceholder').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }

        function openModal(mode, id = null) {
            document.getElementById('charmForm').reset();
            $('#linkedItems').val(null).trigger('change');

            document.getElementById('previewImg').style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'flex';
            document.getElementById('charmId').value = '';
            document.getElementById('existingImage').value = '';

            if (mode === 'edit') {
                document.getElementById('modalTitle').textContent = 'Edit Charm';
                fetch(`charms.php?action=get_charm_details&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        const c = data.charm;
                        document.getElementById('charmId').value = c.CHARM_ID;
                        document.getElementById('charmName').value = c.CHARM_NAME;
                        document.getElementById('charmPrice').value = c.CHARM_PRICE;
                        document.getElementById('charmType').value = c.CHARM_TYPE;
                        document.getElementById('charmMaterial').value = c.CHARM_MATERIAL;
                        document.getElementById('charmCat').value = c.CHARM_COMPATIBLE_CAT;
                        document.getElementById('charmActive').checked = (c.CHARM_ACTIVE == 1);

                        if (c.CHARM_IMAGE) {
                            document.getElementById('existingImage').value = c.CHARM_IMAGE;
                            document.getElementById('previewImg').src = '..' + c.CHARM_IMAGE;
                            document.getElementById('previewImg').style.display = 'block';
                            document.getElementById('uploadPlaceholder').style.display = 'none';
                        }
                        if (data.linked_items) {
                            $('#linkedItems').val(data.linked_items).trigger('change');
                        }
                    });
            } else {
                document.getElementById('modalTitle').textContent = 'Add New Charm';
                // Auto-select "Build-Your-Own" Logic
                $('#linkedItems option').each(function() {
                    if ($(this).text().toLowerCase().includes('build-your-own')) {
                        $('#linkedItems').val([$(this).val()]).trigger('change');
                    }
                });
            }
            document.getElementById('charmModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('charmModal').classList.remove('active');
        }

        function deleteCharm(id) {
            if (confirm('Delete this charm?')) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML =
                    `<input type="hidden" name="action" value="delete_charm"><input type="hidden" name="charm_id" value="${id}">`;
                document.body.appendChild(f);
                f.submit();
            }
        }
    </script>
</body>

</html>