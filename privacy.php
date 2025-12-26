<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #F9F5F0; color: #0B2136; font-family: 'Lato', sans-serif; line-height: 1.6; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .container { max-width: 900px; margin: 60px auto; padding: 50px; background: #ffffff; border: 1px solid #e0e0e0; flex: 1; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #0B2136; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 30px; }
        h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-top: 35px; color: #0B2136; }
        .highlight { color: #d4af37; font-weight: 600; }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    <div class="container">
        <h1>Privacy Policy</h1>
        <p>In compliance with the <span class="highlight">Personal Data Protection Act (PDPA) 2010</span> of Malaysia, Tink is committed to protecting your personal data.</p>
        <h2>Information Collection</h2>
        <p>We collect your name, email, and shipping address solely for processing your jewelry orders and managing your account.</p>
        <h2>Data Security</h2>
        <p>All payment transactions are handled through secure gateways. We do not store raw credit card data on our servers.</p>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>