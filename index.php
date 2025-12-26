<?php
session_start();
require_once "config.php"; // Changed to config.php

// Fetch trending items (LIMIT 4 for a nice grid)
$stmt = $pdo->query("SELECT ITEM_ID, ITEM_NAME, ITEM_PRICE, ITEM_MATERIAL, ITEM_IMAGE FROM ITEM LIMIT 4");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tink Jewelry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link rel="stylesheet" href="assets/css/style.css?v=1">
    <link rel="stylesheet" href="assets/css/home.css?v=1">
</head>

<body>

    <?php include 'components/header.php'; ?>

    <section class="hero" style="background-image: url('assets/images/hero.png');">
        <div class="hero-text">
            <h1>
                HANDMADE JEWELRY FOR<br>
                MOMENTS THAT MATTER
            </h1>
            <a href="catalog.php" class="btn-shop-now">Shop Collection</a>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Trending Jewellery</h2>
            <p class="section-subtitle">Our most loved pieces this season</p>
        </div>

        <div class="products">
            <?php foreach ($products as $row) { ?>
                <div class="product">
                    <a href="product_details.php?id=<?= $row['ITEM_ID']; ?>" class="product-link">
                        <div class="img-container">
                            <img src="<?= htmlspecialchars(ltrim($row['ITEM_IMAGE'], '/')); ?>"
                                alt="<?= htmlspecialchars($row['ITEM_NAME']); ?>">
                        </div>
                        <h4><?= htmlspecialchars($row['ITEM_NAME']); ?></h4>
                        <div class="material"><?= htmlspecialchars($row['ITEM_MATERIAL']); ?></div>
                        <div class="price">MYR <?= number_format($row['ITEM_PRICE'], 2); ?></div>
                    </a>
                </div>
            <?php } ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="catalog.php" class="btn-view-all">View All Products</a>
        </div>
    </section>

    <section class="features" style="background-image: url('assets/images/wave.jpg');">
        <div class="feature-box">

            <div class="feature">
                <i class='bx bx-diamond'></i>
                <h4>Quality Materials</h4>
                <p>Lasting shine & skin-safe</p>
            </div>

            <div class="feature">
                <i class='bx bxs-magic-wand'></i>
                <h4>Customize</h4>
                <p>Personalised just for you</p>
            </div>

            <div class="feature">
                <i class='bx bxs-truck'></i>
                <h4>Fast Delivery</h4>
                <p>Right to your door</p>
            </div>

            <div class="feature">
                <i class='bx bx-dollar-circle'></i>
                <h4>Affordable Price</h4>
                <p>Style within budget</p>
            </div>

        </div>
    </section>

    <section class="promo-section">
        <div class="promo-image" style="background-image: url('assets/images/packaging.png');"></div>

        <div class="promo-content">
            <h3>Premium Gift Packaging</h3>
            <em>Always Complimentary</em>
            <p>
                Whether it’s for someone you love or for yourself, every order comes
                beautifully wrapped with premium gift packaging.
            </p>
            <a href="catalog.php" class="promo-button">Shop Now</a>
        </div>
    </section>

    <section class="promo-section">
        <div class="promo-content">
            <h3>Custom Engraving Service</h3>
            <em>Available on pendants, ring bands, and bracelet plates.</em>
            <p>
                Your jewelry should say something special.
                With our engraving service, every piece becomes uniquely
                yours — crafted with care to celebrate your story.
            </p>
            <a href="catalog.php" class="promo-button">Design Your Moment</a>
        </div>

        <div class="promo-image" style="background-image: url('assets/images/engraving.png');"></div>
    </section>

    <?php include 'components/footer.php'; ?>

</body>

</html>