<?php
session_start();
require_once 'config.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. SAVE ADDRESS (Simplified)
    if (isset($_POST['action']) && $_POST['action'] === 'save_address') {
        $name = trim($_POST['addr_name']);
        // Just grab the whole text block
        $full_address = trim($_POST['shipping_address']);

        $stmt = $pdo->prepare("UPDATE CUSTOMER SET CUSTOMER_NAME = ?, CUSTOMER_ADDRESS = ? WHERE CUSTOMER_ID = ?");
        $stmt->execute([$name, $full_address, $user_id]);

        header("Location: cart.php");
        exit;
    }

    // B. SHIPPING METHOD
    if (isset($_POST['shipping_method'])) {
        $_SESSION['selected_shipping'] = $_POST['shipping_method'];
        header("Location: cart.php");
        exit;
    }

    // C. UPDATE QUANTITY
    if (isset($_POST['action']) && $_POST['action'] === 'update_qty') {
        $cartitem_id = intval($_POST['cartitem_id']);
        $current_qty = intval($_POST['current_qty']);
        $direction = $_POST['direction'];
        $new_qty = ($direction === 'increase') ? $current_qty + 1 : $current_qty - 1;

        if ($new_qty > 0) {
            $stmt = $pdo->prepare("UPDATE CARTITEM SET CARTITEM_QUANTITY = ? WHERE CARTITEM_ID = ?");
            $stmt->execute([$new_qty, $cartitem_id]);
        }
        header("Location: cart.php");
        exit;
    }

    // D. CHANGE VARIANT
    if (isset($_POST['action']) && $_POST['action'] === 'change_variant') {
        $cartitem_id = intval($_POST['cartitem_id']);
        $new_item_id = intval($_POST['new_item_id']);
        $stmt = $pdo->prepare("UPDATE CARTITEM SET ITEM_ID = ? WHERE CARTITEM_ID = ?");
        $stmt->execute([$new_item_id, $cartitem_id]);
        header("Location: cart.php");
        exit;
    }
}

// --- DATA FETCHING ---

// 1. Fetch User Info
$stmt = $pdo->prepare("SELECT CUSTOMER_NAME, CUSTOMER_ADDRESS FROM CUSTOMER WHERE CUSTOMER_ID = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_name = $user_data['CUSTOMER_NAME'];
$current_address = $user_data['CUSTOMER_ADDRESS'];

// 2. Fetch Cart
$cart_items = [];
$subtotal = 0;
$stmt = $pdo->prepare("SELECT CART_ID FROM CART WHERE CUSTOMER_ID = ? AND CART_STATUS = 'active'");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cart) {
    $cart_id = $cart['CART_ID'];
    $sql = "SELECT ci.*, i.ITEM_NAME, i.ITEM_IMAGE, i.ITEM_PRICE, i.ITEM_MATERIAL, i.ITEM_COLOR, i.PARENT_ID, d.DESIGNER_NAME
            FROM CARTITEM ci
            JOIN ITEM i ON ci.ITEM_ID = i.ITEM_ID
            LEFT JOIN DESIGNER d ON i.DESIGNER_ID = d.DESIGNER_ID
            WHERE ci.CART_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $subtotal += ($item['CARTITEM_PRICE'] * $item['CARTITEM_QUANTITY']);
    }
}

$shipping_method = isset($_SESSION['selected_shipping']) ? $_SESSION['selected_shipping'] : 'standard';
$shipping_cost = 7.00;
$total = $subtotal + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Your Cart | TINK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&family=Courier+Prime:wght@400;700&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <style>
        .address-box {
            background: #fdfbf7;
            border: 1px solid #e0e0e0;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .address-header h4 {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            font-size: 0.85rem;
            text-decoration: underline;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-family: 'Lato', sans-serif;
        }

        .address-form {
            display: none;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .field-group {
            margin-bottom: 15px;
        }

        .field-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        /* New Styles for Name Input and Textarea */
        .input-text {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Lato', sans-serif;
            background: #fff;
            box-sizing: border-box;
        }

        .input-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Lato', sans-serif;
            background: #fff;
            resize: vertical;
            line-height: 1.5;
            box-sizing: border-box;
        }

        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-save {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .btn-cancel {
            background: #fff;
            color: #333;
            border: 1px solid #ccc;
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .variant-select {
            margin-top: 5px;
            padding: 6px;
            border: 1px solid #ddd;
            font-family: 'Lato', sans-serif;
            font-size: 0.85rem;
            color: #555;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .shipping-select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-family: 'Courier Prime', monospace;
            font-size: 0.8rem;
            color: #333;
            background: #fff;
            width: 100%;
            max-width: 150px;
            text-align: right;
        }
    </style>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="cart-container">

        <div class="cart-items-section">
            <h1 class="page-title-small">Shopping Bag (<?= count($cart_items) ?>)</h1>

            <div class="address-box">
                <div class="address-header">
                    <h4><i class='bx bxs-map'></i> Shipping Details</h4>
                    <button class="btn-edit" onclick="toggleAddressForm()">Edit</button>
                </div>

                <div id="displayAddress" style="line-height: 1.6; color: #555;">
                    <strong
                        style="display:block; color:#000; margin-bottom:4px; font-size:1.1rem;"><?= htmlspecialchars($current_name) ?></strong>
                    <?php if (!empty($current_address)): ?>
                        <?= nl2br(htmlspecialchars($current_address)) ?>
                    <?php else: ?>
                        <em>No shipping address set. Please click Edit to add one.</em>
                    <?php endif; ?>
                </div>

                <form action="cart.php" method="POST" class="address-form" id="addressForm">
                    <input type="hidden" name="action" value="save_address">

                    <div class="field-group">
                        <label>Recipient Name</label>
                        <input type="text" name="addr_name" class="input-text"
                            value="<?= htmlspecialchars($current_name) ?>" required>
                    </div>

                    <div class="field-group">
                        <label>Full Address</label>
                        <textarea name="shipping_address" class="input-textarea" rows="4"
                            placeholder="Enter your full address here (Street, Unit, City, Zip, Phone, etc.)"
                            required><?= htmlspecialchars($current_address) ?></textarea>
                    </div>

                    <div class="address-actions">
                        <button type="submit" class="btn-save">Save & Update</button>
                        <button type="button" class="btn-cancel" onclick="toggleAddressForm()">Cancel</button>
                    </div>
                </form>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>Your bag is currently empty.</p>
                    <a href="catalog.php" class="btn-shop">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="<?= htmlspecialchars($item['ITEM_IMAGE']) ?>" alt="Product">
                            </div>
                            <div class="item-details">
                                <div class="item-header">
                                    <h3 class="item-name"><?= htmlspecialchars($item['ITEM_NAME']) ?></h3>
                                    <span class="item-price">RM <?= number_format($item['CARTITEM_PRICE'], 2) ?></span>
                                </div>
                                <p class="item-meta"><?= htmlspecialchars($item['DESIGNER_NAME']) ?></p>

                                <?php
                                $siblings = [];
                                if ($item['PARENT_ID']) {
                                    $s_stmt = $pdo->prepare("SELECT ITEM_ID, ITEM_MATERIAL, ITEM_COLOR FROM ITEM WHERE PARENT_ID = ?");
                                    $s_stmt->execute([$item['PARENT_ID']]);
                                    $siblings = $s_stmt->fetchAll(PDO::FETCH_ASSOC);
                                }
                                ?>
                                <?php if (count($siblings) > 1): ?>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="action" value="change_variant">
                                        <input type="hidden" name="cartitem_id" value="<?= $item['CARTITEM_ID'] ?>">
                                        <select name="new_item_id" class="variant-select custom-dropdown"
                                            onchange="this.form.submit()">
                                            <div class="dropdown-options"">
                                    <?php foreach ($siblings as $sib):
                                        $label = $sib['ITEM_MATERIAL'];
                                        if ($sib['ITEM_COLOR']) $label .= " (" . $sib['ITEM_COLOR'] . ")";
                                        $selected = ($sib['ITEM_ID'] == $item['ITEM_ID']) ? 'selected' : '';
                                    ?>
                                    <option class=" dropdown-option" value="<?= $sib['ITEM_ID'] ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            </div>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <p class="item-meta"><?= htmlspecialchars($item['ITEM_MATERIAL']) ?></p>
                                <?php endif; ?>

                                <?php if ($item['CARTITEM_ENGRAVING']): ?>
                                    <p class="item-engraving"><i class='bx bxs-message-square-edit'></i>
                                        "<?= htmlspecialchars($item['CARTITEM_ENGRAVING']) ?>"</p>
                                <?php endif; ?>

                                <div class="item-actions">
                                    <form action="cart.php" method="POST" class="qty-form">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="cartitem_id" value="<?= $item['CARTITEM_ID'] ?>">
                                        <input type="hidden" name="current_qty" value="<?= $item['CARTITEM_QUANTITY'] ?>">
                                        <div class="qty-control">
                                            <button type="submit" name="direction" value="decrease">-</button>
                                            <input type="text" value="<?= $item['CARTITEM_QUANTITY'] ?>" readonly>
                                            <button type="submit" name="direction" value="increase">+</button>
                                        </div>
                                    </form>
                                    <a href="cart_remove.php?id=<?= $item['CARTITEM_ID'] ?>" class="remove-link">Remove</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($cart_items)): ?>
            <div class="cart-summary-section">
                <div class="receipt-wrapper">
                    <div class="receipt-top"></div>
                    <div class="receipt-content">
                        <div class="receipt-header">
                            <h2>TINK RECEIPT</h2>
                            <p><?= date("d M Y") ?> | #<?= str_pad($cart_id, 6, '0', STR_PAD_LEFT) ?></p>
                            <div class="dashed-line"></div>

                            <p style="text-align:left; font-size:0.75rem; margin-bottom:5px; font-weight:700;">SHIP TO:</p>
                            <div
                                style="text-align:left; font-size:0.8rem; margin-bottom:15px; font-family: 'Courier Prime', monospace; line-height: 1.4;">
                                <strong><?= htmlspecialchars($current_name) ?></strong><br>
                                <?= nl2br(htmlspecialchars($current_address)) ?>
                            </div>

                            <div class="dashed-line"></div>
                        </div>

                        <div class="receipt-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="receipt-row">
                                    <span><?= substr($item['ITEM_NAME'], 0, 15) . (strlen($item['ITEM_NAME']) > 15 ? '...' : '') ?>
                                        x<?= $item['CARTITEM_QUANTITY'] ?></span>
                                    <span><?= number_format($item['CARTITEM_PRICE'] * $item['CARTITEM_QUANTITY'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="dashed-line"></div>

                        <div class="receipt-totals">
                            <div class="receipt-row">
                                <span>Subtotal</span>
                                <span><?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="receipt-row">
                                <span style="align-self: center;">Shipping</span>
                                <span>RM <?= number_format($shipping_cost, 2) ?></span>
                            </div>
                            <div class="receipt-row total">
                                <span>TOTAL</span>
                                <span>RM <?= number_format($total, 2) ?></span>
                            </div>
                        </div>

                        <div class="receipt-footer">
                            <p>THANK YOU FOR SHOPPING</p>
                            <div class="barcode">||| || ||| || |||| |||</div>
                        </div>
                    </div>
                    <div class="receipt-bottom"></div>
                </div>

                <form action="checkout_process.php" method="POST">
                    <?php if (!empty($current_address)): ?>
                        <button type="submit" class="btn-checkout">PROCEED TO CHECKOUT</button>
                    <?php else: ?>
                        <button type="button" class="btn-checkout" onclick="alert('Please add a shipping address first.')"
                            style="opacity:0.5;">PROCEED TO CHECKOUT</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'components/footer.php'; ?>

    <script>
        function toggleAddressForm() {
            var form = document.getElementById('addressForm');
            var display = document.getElementById('displayAddress');

            if (form.style.display === 'none' || form.style.display === '') {
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