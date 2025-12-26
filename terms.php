<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms & Conditions | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #F9F5F0; color: #0B2136; font-family: 'Lato', sans-serif; line-height: 1.6; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .container { max-width: 900px; margin: 60px auto; padding: 50px; background: #ffffff; border: 1px solid #e0e0e0; flex: 1; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #0B2136; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 30px; }
        h2 { font-family: 'Playfair Display', serif; color: #d4af37; margin-top: 30px; font-size: 1.4rem; }
        .note { background: #F9F5F0; padding: 15px; border-left: 4px solid #0B2136; font-style: italic; margin-bottom: 20px; }
        p { margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    <div class="container">
        <h1>Terms & Conditions</h1>
        <div class="note">By using the Tink platform and logging into your account, you agree to comply with our purchasing, shipping, and customization policies.</div>
        
        <h2>1. Pricing & Payments</h2>
        <p>Our jewelry is priced between <strong>RM20 and RM60</strong> to remain accessible. Payments are processed securely; Tink does not store raw credit card data on its servers.</p>

        <h2>2. Customization </h2>
        <p>Customers are solely responsible for the accuracy of engraving details. Once an order is placed, custom details cannot be changed. Custom items are non-refundable.</p>

        <h2>3. Shipping Policy </h2>
        <p>Standard shipping is RM10. Orders exceeding RM100 receive free shipping. Delivery timelines are subject to courier availability.</p>

        <h2>4. Data Protection </h2>
        <p>We comply with the Malaysian Personal Data Protection Act. Your account information is encrypted and used only for order processing.</p>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>