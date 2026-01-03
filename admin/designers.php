<?php
session_start();

// Check if the admin_id session variable exists
if (!isset($_SESSION['admin_id'])) {
    // If not logged in, redirect to the login page
    header('Location: login.php');
    exit;
}

require_once '../config.php'; // Your database connection

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ADD DESIGNER
    if ($_POST['action'] === 'add_designer') {
        $name = trim($_POST['designer_name']);
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO DESIGNER (DESIGNER_NAME) VALUES (?)");
                $stmt->execute([$name]);
                $success_msg = "Designer '$name' added successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_msg = "Error: Designer '$name' already exists.";
                } else {
                    $error_msg = "Database Error: " . $e->getMessage();
                }
            }
        } else {
            $error_msg = "Designer name cannot be empty.";
        }
    }

    // EDIT DESIGNER
    if ($_POST['action'] === 'edit_designer') {
        $id = intval($_POST['designer_id']);
        $name = trim($_POST['designer_name']);

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE DESIGNER SET DESIGNER_NAME = ? WHERE DESIGNER_ID = ?");
                $stmt->execute([$name, $id]);
                $success_msg = "Designer updated successfully.";
            } catch (Exception $e) {
                $error_msg = "Error updating designer: " . $e->getMessage();
            }
        }
    }

    // DELETE DESIGNER
    if ($_POST['action'] === 'delete_designer') {
        $id = intval($_POST['designer_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM DESIGNER WHERE DESIGNER_ID = ?");
            $stmt->execute([$id]);
            $success_msg = "Designer deleted successfully.";
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error_msg = "Cannot delete this designer because they have products. Please manage their items first.";
            } else {
                $error_msg = "Error deleting designer: " . $e->getMessage();
            }
        }
    }
}

// --- FETCH DATA ---
$sql = "
    SELECT d.DESIGNER_ID, d.DESIGNER_NAME, COUNT(i.ITEM_ID) as item_count
    FROM DESIGNER d
    LEFT JOIN ITEM i ON d.DESIGNER_ID = i.DESIGNER_ID
    GROUP BY d.DESIGNER_ID
    ORDER BY d.DESIGNER_NAME ASC
";
$designers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_designers = count($designers);
$total_items_linked = array_sum(array_column($designers, 'item_count'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designers - TINK Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                <li><a href="charms.php"><i class='bx bxs-magic-wand'></i> <span>Charms</span></a></li>

                <li>
                    <a href="customers.php"><i class='bx bxs-user-circle'></i> <span>Customers</span></a>
                </li>
                <li>
                    <a href="orders.php"><i class='bx bxs-shopping-bags'></i> <span>Orders</span></a>
                </li>
                <li class="active">
                    <a href="designers.php"><i class='bx bxs-palette'></i> <span>Designers</span></a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header>
            <h2>Designer Management</h2>
            <div class="user-actions">
                <span>Admin</span>
                <a href="/admin/logout.php" class="logout"><i class='bx bx-log-out-circle'></i> Log Out</a>
            </div>
        </header>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error"><i class='bx bx-x-circle'></i> <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Total Designers</div><i class='bx bxs-palette'
                        style="font-size: 1.5rem; color: var(--purple-text);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value"><?php echo $total_designers; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Products Assigned</div><i class='bx bxs-t-shirt'
                        style="font-size: 1.5rem; color: var(--blue-text);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value"><?php echo $total_items_linked; ?></div>
                    <div class="stat-change">Items linked to designers</div>
                </div>
            </div>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Registered Designers</h3>
                <button class="btn-add-product" onclick="openModal('add')"><i class='bx bx-plus'></i> Add
                    Designer</button>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Designer Name</th>
                        <th>Active Products</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($designers) > 0): ?>
                        <?php foreach ($designers as $d): ?>
                            <tr>
                                <td>#<?php echo $d['DESIGNER_ID']; ?></td>
                                <td><span
                                        style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($d['DESIGNER_NAME']); ?></span>
                                </td>
                                <td>
                                    <?php if ($d['item_count'] > 0): ?>
                                        <span class="stock-status stock-medium"
                                            style="background: var(--blue-bg); color: var(--blue-text);"><?php echo $d['item_count']; ?>
                                            Items</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">No items yet</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-buttons" style="justify-content: flex-end;">
                                        <button class="btn-icon" style="background: var(--green-bg); color: var(--green-text);"
                                            onclick="openItemsModal(<?php echo $d['DESIGNER_ID']; ?>, '<?php echo htmlspecialchars(addslashes($d['DESIGNER_NAME'])); ?>')"
                                            title="Manage Items & Stock">
                                            <i class='bx bx-list-ul'></i>
                                        </button>
                                        <button class="btn-icon btn-edit"
                                            onclick="openModal('edit', <?php echo $d['DESIGNER_ID']; ?>, '<?php echo htmlspecialchars(addslashes($d['DESIGNER_NAME'])); ?>')"
                                            title="Edit Name">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button class="btn-icon btn-delete"
                                            onclick="deleteDesigner(<?php echo $d['DESIGNER_ID']; ?>, <?php echo $d['item_count']; ?>)"
                                            title="Delete Designer">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 30px;">No designers found. Add one to get
                                started!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="designerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Designer</h2>
                <button class="btn-close" onclick="closeModal('designerModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add_designer">
                <input type="hidden" name="designer_id" id="designerId">
                <div class="form-group">
                    <label>Designer Name</label>
                    <input type="text" name="designer_name" id="designerName" required placeholder="e.g. Tiffany & Co.">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('designerModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Save Designer</button>
                </div>
            </form>
        </div>
    </div>

    <div id="itemsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="itemsModalTitle">Manage Items</h2>
                <button class="btn-close" onclick="closeModal('itemsModal')">&times;</button>
            </div>
            <div id="itemsLoading" style="text-align: center; padding: 20px;">
                <i class='bx bx-loader-alt bx-spin' style="font-size: 2rem; color: var(--orange-accent);"></i>
            </div>
            <div id="itemsBody" style="display: none;">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th style="width: 120px;">Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList"></tbody>
                </table>
                <div id="noItemsMsg"
                    style="text-align: center; padding: 20px; color: var(--text-secondary); display: none;">
                    This designer has no items assigned yet.
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- DESIGNER MODAL ---
        function openModal(mode, id = null, name = '') {
            const modal = document.getElementById('designerModal');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            const dId = document.getElementById('designerId');
            const dName = document.getElementById('designerName');

            if (mode === 'edit') {
                title.innerText = 'Edit Designer';
                action.value = 'edit_designer';
                dId.value = id;
                dName.value = name;
            } else {
                title.innerText = 'Add New Designer';
                action.value = 'add_designer';
                dId.value = '';
                dName.value = '';
            }
            modal.classList.add('active');
        }

        // --- ITEMS & STOCK MODAL ---
        function openItemsModal(designerId, designerName) {
            const modal = document.getElementById('itemsModal');
            const title = document.getElementById('itemsModalTitle');
            const loader = document.getElementById('itemsLoading');
            const body = document.getElementById('itemsBody');
            const list = document.getElementById('itemsList');
            const noMsg = document.getElementById('noItemsMsg');

            title.innerText = `Items by ${designerName}`;
            modal.classList.add('active');
            loader.style.display = 'block';
            body.style.display = 'none';

            // Fetch Items
            fetch(`api/get-designer-items.php?id=${designerId}`)
                .then(res => res.json())
                .then(data => {
                    loader.style.display = 'none';
                    body.style.display = 'block';
                    list.innerHTML = '';

                    if (data.length > 0) {
                        noMsg.style.display = 'none';
                        data.forEach(item => {
                            // --- FIX: PATH CORRECTION LOGIC START ---
                            let imgPath = '../assets/img/no-img.png'; // Default
                            if (item.ITEM_IMAGE) {
                                // 1. Remove leading slash: /images/products/foo.jpg -> images/products/foo.jpg
                                const cleanPath = item.ITEM_IMAGE.replace(/^\//, '');
                                // 2. Prepend ../ to go up from 'admin' to 'root'
                                imgPath = '../' + cleanPath;
                            }
                            // --- FIX: PATH CORRECTION LOGIC END ---

                            const row = document.createElement('tr');
                            row.innerHTML = `
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="${imgPath}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                    <span>${item.ITEM_NAME}</span>
                                </div>
                            </td>
                            <td><span class="category-badge">${item.ITEM_CATEGORY}</span></td>
                            <td>RM ${parseFloat(item.ITEM_PRICE).toFixed(2)}</td>
                            <td>
                                <input type="number" id="stock-${item.ITEM_ID}" value="${item.ITEM_STOCK}" min="0" 
                                     style="width: 80px; padding: 5px; border: 1px solid var(--border-color); border-radius: 4px;">
                            </td>
                            <td>
                                <button class="btn-submit" style="padding: 5px 10px; font-size: 0.8rem;" onclick="updateStock(${item.ITEM_ID})">
                                    Update
                                </button>
                            </td>
                        `;
                            list.appendChild(row);
                        });
                    } else {
                        noMsg.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Failed to load items.");
                    closeModal('itemsModal');
                });
        }

        function updateStock(itemId) {
            const input = document.getElementById(`stock-${itemId}`);
            const newStock = input.value;
            const btn = input.parentElement.nextElementSibling.querySelector('button');

            const originalText = btn.innerText;
            btn.innerText = '...';
            btn.disabled = true;

            fetch('api/update-stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `item_id=${itemId}&stock=${newStock}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.style.background = 'var(--green-bg)';
                        btn.style.color = 'var(--green-text)';
                        btn.innerText = 'Saved!';
                        setTimeout(() => {
                            btn.style.background = '';
                            btn.style.color = 'white';
                            btn.innerText = originalText;
                            btn.disabled = false;
                        }, 1500);
                    } else {
                        alert(data.error || 'Update failed');
                        btn.innerText = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    alert('Error updating stock');
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function deleteDesigner(id, itemCount) {
            if (itemCount > 0) {
                alert("Cannot delete this designer because they still have products. Please delete items first.");
                return;
            }
            if (confirm("Are you sure you want to delete this designer?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML =
                    `<input type="hidden" name="action" value="delete_designer"><input type="hidden" name="designer_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Outside click closes modal
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>

</body>

</html>