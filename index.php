<?php
require_once "db_home.php";

$stmt = $pdo->query("SELECT name, price, material, image FROM products LIMIT 5");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tink Jewelry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=1">
    <link rel="stylesheet" href="assets/css/home.css?v=1">

</head>

<body>



    <!-- ===== Header ===== -->
    <?php include 'components/header.php'; ?>

    <!-- ===== Hero Section ===== -->
    <section class="hero" style="background-image: url('assets/images/hero.png');">
        <div class="hero-text">
            <h1>
                HANDMADE JEWELRY FOR<br>
                MOMENTS THAT MATTER
            </h1>
        </div>
    </section>

    <!-- ===== Trending Section ===== -->
    <section class="section">
        <h2>Trending Jewellery</h2>

<div class="products">
    <?php foreach ($products as $row) { ?>
        <div class="product">
            <img src="assets/images/<?php echo htmlspecialchars($row['image']); ?>">
            <h4><?php echo htmlspecialchars($row['name']); ?></h4>
            <div class="price">MYR <?php echo htmlspecialchars($row['price']); ?></div>
            <div class="material"><?php echo htmlspecialchars($row['material']); ?></div>
        </div>
    <?php } ?>
</div>

     </section>

    <!-- ===== Features ===== -->
    <section class="features" style="background-image: url('assets/images/wave.jpg');">
        <div class="feature-box">

            <div class="feature">
                <i class="fa-regular fa-gem"></i>
                <h4>Quality Materials</h4>
                <p>Lasting shine & skin-safe</p>
            </div>

            <div class="feature">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <h4>Customize</h4>
                <p>Personalised just for you</p>
            </div>

            <div class="feature">
                <i class="fa-solid fa-truck"></i>
                <h4>Fast Delivery</h4>
                <p>Right to your door</p>
            </div>

            <div class="feature">
                <i class="fa-solid fa-dollar-sign"></i>
                <h4>Affordable Price</h4>
                <p>Style within budget</p>
            </div>

        </div>
    </section>

    <!-- ===== Premium Packaging ===== -->
    <section class="promo-section">
        <div class="promo-image" style="background-image: url('assets/images/packaging.png');"></div>

        <div class="promo-content">
            <h3>Premium Gift Packaging</h3>
            <em>Always Complimentary</em>
            <p>
                Whether it’s for someone you love or for yourself, every order comes
                beautifully wrapped with premium gift packaging.
            </p>
            <button class="promo-button">Get Premium Packaging</button>
        </div>
    </section>

    <!-- ===== Custom Engraving ===== -->
    <section class="promo-section">
        <div class="promo-content">
            <h3>Custom Engraving Service</h3>
            <em>Available on pendants, ring bands, and
                bracelet plates—add names, initials, dates,
                or short messages for a personal touch</em>
            <p>
                Your jewelry should say something special.
                With our engraving service, every piece becomes uniquely
                yours — crafted with care to celebrate your story.
            </p>
            <button class="promo-button">Design Your Moment</button>
        </div>

        <div class="promo-image" style="background-image: url('assets/images/engraving.png');"></div>
    </section>

    <!-- ===== Footer ===== -->
    <footer class="site-footer">

        <div class="footer-grid">

            <div>
                <h4>Info</h4>
                <a href="#">Terms & Conditions</a>
                <a href="#">Privacy & Policy</a>
                <a href="#">FAQ</a>
            </div>

            <div>
                <h4>Customer Service</h4>
                <p><i class="fa-solid fa-phone"></i> 013-8974568</p>
                <p><i class="fa-solid fa-envelope"></i> tink@gmail.com</p>
            </div>

            <div>
                <h4>Follow Us</h4>
                <div class="footer-social">
                    <i class="fa-brands fa-facebook"></i>
                    <i class="fa-brands fa-instagram"></i>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            © <?php echo date("Y"); ?> Tink. All Rights Reserved
        </div>

    </footer>

</body>

</html>