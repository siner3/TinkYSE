<?php
session_start();
// Optional: Get Order ID from URL to show reference number
$order_id = isset($_GET['order_id']) ? str_pad($_GET['order_id'], 6, '0', STR_PAD_LEFT) : '---';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thank You | TINK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .thankyou-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            padding: 40px;
            background: #fdfbf7;
            border: 1px solid #e0e0e0;
        }

        .thankyou-icon {
            font-size: 4rem;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .thankyou-title {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            margin-bottom: 15px;
            color: #333;
        }

        .thankyou-msg {
            font-family: 'Lato', sans-serif;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-ref {
            font-weight: bold;
            color: #000;
        }

        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .btn-home:hover {
            background: #333;
        }
    </style>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="thankyou-container">
        <i class='bx bx-check-circle thankyou-icon'></i>
        <h1 class="thankyou-title">Thank You!</h1>
        <p class="thankyou-msg">
            Your order has been placed successfully.<br>
            Order Reference: <span class="order-ref">#<?= $order_id ?></span><br><br>
            We are preparing your jewelry with care. You will receive an update once it has been shipped.
        </p>

        <a href="index.php" class="btn-home">Return to Home</a>
        <br><br>
        <a href="account.php" style="font-size: 0.8rem; color: #666; text-decoration: underline;">View Order History</a>
    </div>

    <?php include 'components/footer.php'; ?>
</body>

</html>