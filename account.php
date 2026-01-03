<?php
session_start();
require_once 'config.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// --- HANDLE POST ACTIONS (Update Profile) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        $stmt = $pdo->prepare("UPDATE CUSTOMER SET CUSTOMER_NAME = ?, CUSTOMER_TEL = ?, CUSTOMER_ADDRESS = ? WHERE CUSTOMER_ID = ?");
        $stmt->execute([$name, $phone, $address, $user_id]);

        header("Location: account.php");
        exit;
    }
}

// --- DATA FETCHING ---
$stmt = $pdo->prepare("SELECT * FROM CUSTOMER WHERE CUSTOMER_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Orders (Carts that are NOT 'active')
// Added TRACKING_ID to the select just to be explicit, though * selects all
$order_sql = "SELECT * FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS != 'active' ORDER BY CART_ID DESC";
$stmt = $pdo->prepare($order_sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Account | TINK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    /* Add specific styles for tracking info */
    .tracking-info {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        font-size: 0.9rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tracking-number {
        font-family: 'Courier Prime', monospace;
        font-weight: 700;
        color: #333;
        background: #f5f5f5;
        padding: 2px 8px;
        border-radius: 4px;
    }

    /* Status Colors */
    .status-processing {
        color: #f59e0b;
        background: #fef3c7;
    }

    .status-shipped {
        color: #3b82f6;
        background: #dbeafe;
    }

    .status-completed {
        color: #10b981;
        background: #d1fae5;
    }

    .status-cancelled {
        color: #ef4444;
        background: #fee2e2;
    }

    .order-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    </style>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="account-container">
        <div class="account-header">
            <h1 class="page-title-small">My Account</h1>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>

        <div class="account-grid">
            <div class="profile-section">
                <div class="info-box">
                    <div class="box-header">
                        <h3><i class='bx bxs-user-detail'></i> Personal Details</h3>
                        <button class="btn-edit" onclick="toggleProfileForm()">Edit</button>
                    </div>
                    <div id="profileDisplay">
                        <div class="detail-row"><span class="label">Name</span><span
                                class="value"><?= htmlspecialchars($user['CUSTOMER_NAME']) ?></span></div>
                        <div class="detail-row"><span class="label">Email</span><span
                                class="value"><?= htmlspecialchars($user['CUSTOMER_EMAIL']) ?></span></div>
                        <div class="detail-row"><span class="label">Phone</span><span
                                class="value"><?= htmlspecialchars($user['CUSTOMER_TEL']) ?></span></div>
                        <div class="detail-row"><span class="label">Address</span><span
                                class="value address-text"><?= !empty($user['CUSTOMER_ADDRESS']) ? nl2br(htmlspecialchars($user['CUSTOMER_ADDRESS'])) : '<em>No address set</em>' ?></span>
                        </div>
                    </div>
                    <form action="account.php" method="POST" id="profileForm" class="edit-form" style="display:none;">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group"><label>Full Name</label><input type="text" name="name"
                                value="<?= htmlspecialchars($user['CUSTOMER_NAME']) ?>" required></div>
                        <div class="form-group"><label>Phone Number</label><input type="text" name="phone"
                                value="<?= htmlspecialchars($user['CUSTOMER_TEL']) ?>" required></div>
                        <div class="form-group"><label>Shipping Address</label><textarea name="address" rows="4"
                                required><?= htmlspecialchars($user['CUSTOMER_ADDRESS']) ?></textarea></div>
                        <div class="form-actions"><button type="submit" class="btn-save">Save Changes</button><button
                                type="button" class="btn-cancel" onclick="toggleProfileForm()">Cancel</button></div>
                    </form>
                </div>
            </div>

            <div class="orders-section">
                <h3><i class='bx bxs-package'></i> Order History</h3>

                <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="catalog.php" class="btn-shop">Start Shopping</a>
                </div>
                <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $order):
                            // Fetch Items
                            $cart_id = $order['CART_ID'];
                            $item_sql = "SELECT ci.*, i.ITEM_NAME, i.ITEM_IMAGE 
                                     FROM CARTITEM ci 
                                     JOIN ITEM i ON ci.ITEM_ID = i.ITEM_ID 
                                     WHERE ci.CART_ID = ?";
                            $i_stmt = $pdo->prepare($item_sql);
                            $i_stmt->execute([$cart_id]);
                            $items = $i_stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Calculate Totals
                            $order_total = 0;
                            foreach ($items as $i) {
                                $order_total += ($i['CARTITEM_PRICE'] * $i['CARTITEM_QUANTITY']);
                            }
                            $shipping = ($order_total > 200) ? 0 : 15;
                            $final_total = $order_total + $shipping;

                            // Status Logic for CSS class
                            $statusClass = 'status-' . strtolower($order['CART_STATUS']);
                        ?>

                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order
                                    #<?= str_pad($order['CART_ID'], 6, '0', STR_PAD_LEFT) ?></span>
                                <span class="order-status <?= $statusClass ?>">
                                    <?= strtoupper($order['CART_STATUS']) ?>
                                </span>
                            </div>
                            <span class="order-total">RM <?= number_format($final_total, 2) ?></span>
                        </div>

                        <div class="order-items-preview">
                            <?php foreach ($items as $item): ?>
                            <div class="mini-item">
                                <img src="<?= htmlspecialchars($item['ITEM_IMAGE']) ?>" alt="Item">
                                <div class="mini-info">
                                    <span class="mini-name"><?= htmlspecialchars($item['ITEM_NAME']) ?></span>
                                    <span class="mini-qty">x<?= $item['CARTITEM_QUANTITY'] ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($order['TRACKING_ID'])): ?>
                        <div class="tracking-info">
                            <i class='bx bxs-truck' style="font-size: 1.2rem; color: #3b82f6;"></i>
                            <span>Tracking ID: <span
                                    class="tracking-number"><?= htmlspecialchars($order['TRACKING_ID']) ?></span></span>

                            <a href="#"
                                style="margin-left: auto; font-size: 0.8rem; color: #3b82f6; text-decoration: underline;">Track
                                Parcel</a>
                        </div>
                        <?php elseif ($order['CART_STATUS'] == 'shipped' && empty($order['TRACKING_ID'])): ?>
                        <div class="tracking-info">
                            <i class='bx bx-loader-circle bx-spin'></i>
                            <span>Tracking info updating...</span>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script>
    function toggleProfileForm() {
        var display = document.getElementById('profileDisplay');
        var form = document.getElementById('profileForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            display.style.display = 'none';
        } else {
            form.style.display = 'none';
            display.style.display = 'block';
        }
    }
    </script>
</body>

</html>