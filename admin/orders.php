<?php
session_start();

// Check if the admin_id session variable exists
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 1. UPDATE STATUS & TRACKING
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['status'];

        try {
            // A. Update Order Status
            $stmt = $pdo->prepare("UPDATE `ORDER` SET ORDER_STATUS = ? WHERE ORDER_ID = ?");
            $stmt->execute([$new_status, $order_id]);

            // B. If Shipped, Update Tracking Number in CART table
            if ($new_status === 'shipped' && !empty($_POST['tracking_id'])) {
                $tracking_id = trim($_POST['tracking_id']);

                // Find the Cart ID linked to this Order
                $stmtCart = $pdo->prepare("SELECT CART_ID FROM `ORDER` WHERE ORDER_ID = ?");
                $stmtCart->execute([$order_id]);
                $cart_id = $stmtCart->fetchColumn();

                if ($cart_id) {
                    $stmtTrack = $pdo->prepare("UPDATE CART SET TRACKING_ID = ? WHERE CART_ID = ?");
                    $stmtTrack->execute([$tracking_id, $cart_id]);
                }
            }

            $success_msg = "Order #$order_id updated to " . ucfirst($new_status);
        } catch (Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }

    // 2. DELETE ORDER
    if ($_POST['action'] === 'delete_order') {
        $order_id = intval($_POST['order_id']);
        try {
            $pdo->prepare("DELETE FROM PAYMENT WHERE ORDER_ID = ?")->execute([$order_id]);
            $pdo->prepare("DELETE FROM `ORDER` WHERE ORDER_ID = ?")->execute([$order_id]);
            $success_msg = "Order #$order_id deleted successfully.";
        } catch (Exception $e) {
            $error_msg = "Error deleting order: " . $e->getMessage();
        }
    }
}

// --- FETCH DATA ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (o.ORDER_ID LIKE ? OR c.CUSTOMER_NAME LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $where .= " AND o.ORDER_STATUS = ?";
    $params[] = $status_filter;
}

// JOIN ORDER + CUSTOMER + CART (To get Tracking ID)
$join_sql = "FROM `ORDER` o 
             JOIN CUSTOMER c ON o.CUSTOMER_ID = c.CUSTOMER_ID
             LEFT JOIN CART ca ON o.CART_ID = ca.CART_ID";

// Count Total
$count_stmt = $pdo->prepare("SELECT COUNT(*) $join_sql WHERE $where");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Fetch Rows
$sql = "SELECT o.*, c.CUSTOMER_NAME, ca.TRACKING_ID,
        (SELECT SUM(CARTITEM_QUANTITY) FROM CARTITEM WHERE CART_ID = o.CART_ID) as item_count 
        $join_sql 
        WHERE $where 
        ORDER BY o.ORDER_DATE DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ORDER_STATUS = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN ORDER_STATUS = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(ORDER_TOTAL) as revenue
    FROM `ORDER`")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - TINK Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Tracking Input Style inside Modal */
        #trackingInput {
            display: none;
            /* Hidden by default */
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .status-shipped {
            background-color: #dbeafe;
            color: #1e40af;
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
                <li><a href="charms.php"><i class='bx bxs-magic-wand'></i> <span>Charms</span></a></li>

                <li>
                    <a href="customers.php"><i class='bx bxs-user-circle'></i> <span>Customers</span></a>
                </li>
                <li class="active">
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
            <h2>Order Management</h2>
            <div class="user-actions">
                <span>Admin</span>
                <a href="logout.php" class="logout"><i class='bx bx-log-out-circle'></i> Log Out</a>
            </div>
        </header>

        <?php if (isset($success_msg)): ?><div class="alert alert-success"><i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <?php if (isset($error_msg)): ?><div class="alert alert-error"><i class='bx bx-x-circle'></i>
                <?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Total Revenue</div><i class='bx bx-money'
                        style="font-size: 1.5rem; color: var(--green-text);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value">RM <?php echo number_format($stats['revenue'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Total Orders</div><i class='bx bx-shopping-bag'
                        style="font-size: 1.5rem; color: var(--text-light);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Pending</div><i class='bx bx-time'
                        style="font-size: 1.5rem; color: var(--yellow-text);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                    <div class="trend negative">Needs Action</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="card-header">
                    <div class="stat-label">Completed</div><i class='bx bx-check-circle'
                        style="font-size: 1.5rem; color: var(--blue-text);"></i>
                </div>
                <div class="card-body">
                    <div class="stat-value"><?php echo number_format($stats['completed'] ?? 0); ?></div>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" style="display: contents;">
                <div class="filter-group" style="flex: 2;">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search Order ID or Customer Name..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>
                            Confirmed</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped
                        </option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>
                            Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                            Cancelled</option>
                    </select>
                </div>
                <div class="filter-group" style="flex: 0; min-width: auto;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-filter"><i class='bx bx-search'></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Recent Orders</h3>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_orders); ?> of
                    <?php echo $total_orders; ?>
                </span>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td data-label="Order ID"><span
                                        style="font-weight: 600; font-family: monospace;">#<?php echo $order['ORDER_ID']; ?></span>
                                </td>
                                <td data-label="Customer"><?php echo htmlspecialchars($order['CUSTOMER_NAME'] ?? 'Guest'); ?>
                                </td>
                                <td data-label="Date"><?php echo date('d M Y, h:i A', strtotime($order['ORDER_DATE'])); ?></td>
                                <td data-label="Items"><?php echo $order['item_count'] ?? 0; ?> items</td>
                                <td data-label="Total"><strong>RM
                                        <?php echo number_format($order['ORDER_TOTAL'], 2); ?></strong></td>
                                <td data-label="Status">
                                    <span
                                        class="status-badge <?php echo strtolower($order['ORDER_STATUS']); ?>"><?php echo ucfirst($order['ORDER_STATUS']); ?></span>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-edit"
                                            onclick="viewOrder(<?php echo $order['ORDER_ID']; ?>, '<?php echo $order['TRACKING_ID'] ?? ''; ?>')"
                                            title="View Details"><i class='bx bx-show'></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a
                            href="?page=1&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">&laquo;
                            First</a>
                        <a
                            href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Prev</a>
                    <?php endif; ?>
                    <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a
                            href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        <a
                            href="?page=<?php echo $total_pages; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Last
                            &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalOrderTitle">Order Details</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalLoading" style="text-align:center; padding: 20px;"><i class='bx bx-loader-alt bx-spin'
                    style="font-size: 2rem; color: var(--orange-accent);"></i></div>
            <div id="modalBody" style="display:none;">
                <div
                    style="background: var(--bg-color); padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between;">
                    <div>
                        <small style="color: var(--text-secondary); text-transform: uppercase;">Customer</small><br>
                        <strong id="modalCustomer" style="font-size: 1.1rem;"></strong><br>
                        <span id="modalDate" style="color: var(--text-secondary); font-size: 0.9rem;"></span>
                    </div>
                    <div style="text-align: right;">
                        <small style="color: var(--text-secondary); text-transform: uppercase;">Order ID</small><br>
                        <strong id="modalId" style="font-size: 1.2rem; font-family: monospace;"></strong>
                    </div>
                </div>

                <table class="styled-table" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="modalItems"></tbody>
                </table>

                <div style="text-align: right; margin-bottom: 20px; font-size: 1.2rem;">Total: <strong id="modalTotal"
                        style="color: var(--orange-accent);"></strong></div>

                <form method="POST" style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="formOrderId">
                    <div class="form-group">
                        <label>Update Order Status</label>
                        <div style="display: flex; gap: 10px; flex-wrap:wrap;">
                            <select name="status" id="modalStatusSelect" onchange="toggleTracking(this)">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <input type="text" name="tracking_id" id="trackingInput"
                                placeholder="Tracking ID (e.g. JNT-8812)">

                            <button type="submit" class="btn-submit">Update</button>
                        </div>
                    </div>
                </form>

                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" onclick="deleteOrder()"
                        style="color: var(--danger); background: none; border: none; cursor: pointer; text-decoration: underline;">Delete
                        this Order Permanently</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('orderModal');

        // Toggle Tracking Input Visibility
        function toggleTracking(el) {
            const input = document.getElementById('trackingInput');
            if (el.value === 'shipped') {
                input.style.display = 'block';
                input.required = true;
            } else {
                input.style.display = 'none';
                input.required = false;
            }
        }

        // View Order (Takes existing tracking ID now)
        function viewOrder(id, existingTracking) {
            modal.classList.add('active');
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalBody').style.display = 'none';

            fetch(`api/get-order.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        closeModal();
                        return;
                    }

                    document.getElementById('modalId').innerText = '#' + data.order.ORDER_ID;
                    document.getElementById('formOrderId').value = data.order.ORDER_ID;
                    document.getElementById('modalCustomer').innerText = data.order.CUSTOMER_NAME || 'Unknown';
                    document.getElementById('modalDate').innerText = new Date(data.order.ORDER_DATE)
                        .toLocaleDateString();
                    document.getElementById('modalTotal').innerText = 'RM ' + parseFloat(data.order.ORDER_TOTAL)
                        .toFixed(2);

                    // Status & Tracking Logic
                    const statusSelect = document.getElementById('modalStatusSelect');
                    statusSelect.value = data.order.ORDER_STATUS;

                    const trackInput = document.getElementById('trackingInput');
                    // Use the passed existingTracking OR what's in the DB if available
                    trackInput.value = existingTracking || '';

                    // Trigger visibility check
                    toggleTracking(statusSelect);

                    const tbody = document.getElementById('modalItems');
                    tbody.innerHTML = '';
                    if (data.items && data.items.length > 0) {
                        data.items.forEach(item => {
                            const total = (item.CARTITEM_PRICE * item.CARTITEM_QUANTITY).toFixed(2);
                            const row = `<tr>
                            <td><div style="display: flex; align-items: center; gap: 10px;">
                                <img src="${item.ITEM_IMAGE || '../assets/img/no-img.png'}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                <span>${item.ITEM_NAME}</span>
                            </div></td>
                            <td style="text-align: center;">${item.CARTITEM_QUANTITY}</td>
                            <td style="text-align: right;">RM ${parseFloat(item.CARTITEM_PRICE).toFixed(2)}</td>
                            <td style="text-align: right; font-weight: 600;">RM ${total}</td>
                        </tr>`;
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No items found.</td></tr>';
                    }

                    document.getElementById('modalLoading').style.display = 'none';
                    document.getElementById('modalBody').style.display = 'block';
                })
                .catch(err => {
                    console.error(err);
                    alert("Failed to load details.");
                    closeModal();
                });
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        function deleteOrder() {
            const id = document.getElementById('formOrderId').value;
            if (confirm("Are you sure you want to delete Order #" + id + "?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML =
                    `<input type="hidden" name="action" value="delete_order"><input type="hidden" name="order_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    </script>
</body>

</html>