<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tuk Jewelry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f4ec;
            color: #222;
        }

        /* Top Banner */
        .top-banner {
            background: #7fb3c8;
            color: #fff;
            text-align: center;
            padding: 6px;
            font-size: 14px;
        }

        /* Navbar */
        header {
            position: absolute;
            top: 30px;
            width: 100%;
            padding: 20px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            z-index: 10;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 600;
        }

        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: #fff;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .nav-icons i {
            margin-left: 18px;
            cursor: pointer;
        }

        /* Hero */
        .hero {
            height: 90vh;
            background: url("assets/images/hero.png") center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.45);
        }

        .hero-text {
            position: relative;
            text-align: center;
            color: #fff;
            font-family: 'Playfair Display', serif;
            letter-spacing: 5px;
        }

        .hero-text h1 {
            font-size: 42px;
            font-weight: 400;
        }

        /* Trending */
        .section {
            padding: 70px 80px;
            background: #f8f4ec;
        }

        .section h2 {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin-bottom: 40px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
        }

        .product {
            background: #fff;
            border-radius: 14px;
            padding: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .product:hover {
            transform: translateY(-6px);
        }

        .product img {
            width: 100%;
            border-radius: 12px;
            height: 220px;
            object-fit: cover;
        }

        .product h4 {
            margin: 12px 0 5px;
            font-size: 15px;
        }

        .price {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .material {
            font-size: 12px;
            color: #888;
        }

        /* Features */
        .features {
            background: url("assets/images/wave.png") center/cover no-repeat;
            padding: 70px 40px;
        }

        .feature-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .feature i {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .feature h4 {
            margin-bottom: 5px;
            font-weight: 500;
        }

        .feature p {
            font-size: 13px;
            color: #333;
        }

        /* Promo Sections */
.promo-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 420px;
}

.promo-image {
    background-size: cover;
    background-position: center;
}

.promo-content {
    background: #f8f4ec;
    padding: 80px 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.promo-content h3 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    margin-bottom: 10px;
}

.promo-content em {
    font-size: 14px;
    display: block;
    margin-bottom: 15px;
    color: #555;
}

.promo-content p {
    font-size: 14px;
    line-height: 1.8;
    max-width: 420px;
    margin-bottom: 25px;
    color: #444;
}

.promo-content button {
    background: #0b2239;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 20px;
    width: fit-content;
    cursor: pointer;
    font-size: 13px;
}

.promo-content button:hover {
    opacity: 0.85;
}

/* Footer */
.site-footer {
    background: #f8f4ec;
    padding: 60px 80px 20px;
    font-size: 13px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 40px;
}

.footer-grid h4 {
    margin-bottom: 15px;
    font-weight: 500;
}

.footer-grid p, 
.footer-grid a {
    color: #333;
    text-decoration: none;
    margin-bottom: 8px;
    display: block;
}

.footer-social i {
    margin-right: 12px;
    font-size: 16px;
    cursor: pointer;
}

.footer-bottom {
    text-align: center;
    margin-top: 40px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    font-size: 12px;
    color: #555;
}


/* Responsive */
@media(max-width: 900px) {
    .promo-section {
        grid-template-columns: 1fr;
    }

    .promo-content {
        padding: 50px 30px;
    }
}

        @media(max-width: 768px) {
            header {
                padding: 20px;
            }

            .hero-text h1 {
                font-size: 28px;
                letter-spacing: 3px;
            }

            .section {
                padding: 50px 20px;
            }
        }
    </style>
</head>
<body>

<!-- Top Banner -->
<div class="top-banner">
    Up To 30% OFF For Christmas Gift
</div>

<!-- Header -->
<header>
    <div class="logo">Tink</div>

    <nav>
        <a href="#">BRACELETS</a>
        <a href="#">NECKLACES</a>
        <a href="#">EARRINGS</a>
        <a href="#">RINGS</a>
        <a href="#">CHARMS</a>
        <a href="#">DESIGNERS</a>
    </nav>

    <div class="nav-icons">
        <i class="fa-solid fa-magnifying-glass"></i>
        <i class="fa-solid fa-cart-shopping"></i>
        <i class="fa-regular fa-user"></i>
    </div>
</header>

<!-- Hero -->
<section class="hero">
    <div class="hero-text">
        <h1>HANDMADE JEWELRY FOR<br> MOMENTS THAT MATTER</h1>
    </div>
</section>

<!-- Trending -->
<section class="section">
    <h2>Trending Jewellery</h2>

    <div class="products">
        <div class="product">
            <img src="assets/images/ring1.png">
            <h4>"Little Flower" Adjustable Ring</h4>
            <div class="price">MYR 33</div>
            <div class="material">Silver-plated copper</div>
        </div>

        <div class="product">
            <img src="assets/images/bracelet1.png">
            <h4>Ocean Wave Elegance Bracelet</h4>
            <div class="price">MYR 48</div>
            <div class="material">Silver sterling & resin</div>
        </div>

        <div class="product">
            <img src="assets/images/earring1.png">
            <h4>Ocean Whisper Earrings</h4>
            <div class="price">MYR 29</div>
            <div class="material">Hypoallergenic alloy</div>
        </div>

        <div class="product">
            <img src="assets/images/ring2.png">
            <h4>Eternal Bloom Ring</h4>
            <div class="price">MYR 39</div>
            <div class="material">Silver Sterling</div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features">
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
<!-- Premium Gift Packaging -->
<section class="promo-section">
    <div class="promo-image" style="background-image: url('assets/images/packaging.png');"></div>

    <div class="promo-content">
        <h3>Premium Gift Packaging</h3>
        <em>Always Complimentary</em>
        <p>
            Whether it’s for someone you love or for yourself, every order comes
            beautifully wrapped with premium gift packaging.
            It’s our way of saying thank you.
        </p>
        <button>Get Premium Packaging</button>
    </div>
</section>

<!-- Custom Engraving -->
<section class="promo-section">
    <div class="promo-content">
        <h3>Custom Engraving Service</h3>
        <em>Available on pendants, ring bands, and bracelets</em>
        <p>
            Add names, initials, dates, or short messages for a personal touch.
            Your jewelry should say something special — crafted with care
            to celebrate your story.
        </p>
        <button>Design Your Moment</button>
    </div>

    <div class="promo-image" style="background-image: url('assets/images/engraving.png');"></div>
</section>

<!-- Footer -->
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
