<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>"Little Flower" Adjustable Ring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
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
            color: #1c1c1c;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Playfair Display', serif;
            font-weight: 400;
        }

        /* Top Bar */
        header {
            background: #1f3b4a;
            color: #fff;
            padding: 16px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 28px;
            font-family: 'Playfair Display', serif;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .nav-icons i {
            margin-left: 16px;
            cursor: pointer;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            padding: 50px 20px 30px;
        }

        .page-title h1 {
            font-size: 36px;
            letter-spacing: 4px;
            color: #7a9bb0;
        }

        /* Layout */
        .container {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 40px;
            padding: 30px 60px 80px;
        }

        /* Filters */
        .filters h4 {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .filter-group {
            margin-bottom: 25px;
        }

        .filter-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 8px;
        }

        /* Products Grid */
        .top-controls {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-bottom: 25px;
        }

        .top-controls select {
            padding: 6px 10px;
            font-size: 12px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
        }

        .product {
            background: #fff;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .product img {
            width: 100%;
            border-radius: 12px;
            height: 220px;
            object-fit: cover;
        }

        .product h4 {
            font-size: 14px;
            margin: 12px 0 6px;
        }

        .product .designer {
            font-size: 11px;
            color: #777;
            margin-bottom: 6px;
        }

        .tags span {
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 2px 8px;
            margin: 2px;
            display: inline-block;
        }

        .price {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        /* Footer */
        footer {
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
            margin-bottom: 12px;
        }

        .footer-grid a,
        .footer-grid p {
            text-decoration: none;
            color: #333;
            display: block;
            margin-bottom: 6px;
        }

        .footer-social i {
            margin-right: 12px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
        }

        @media(max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">Tuk</div>
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

    <div class="page-title">
        <h1>NECKLACES</h1>
    </div>

    <div class="container">
        <!-- Filters -->
        <aside class="filters">
            <div class="filter-group">
                <h4>Gender</h4>
                <label><input type="checkbox"> Men</label>
                <label><input type="checkbox"> Women</label>
                <label><input type="checkbox"> Unisex</label>
            </div>

            <div class="filter-group">
                <h4>Style</h4>
                <label><input type="checkbox"> Minimal</label>
                <label><input type="checkbox"> Elegant</label>
            </div>

            <div class="filter-group">
                <h4>Material</h4>
                <label><input type="checkbox"> Silver</label>
                <label><input type="checkbox"> Gold</label>
            </div>

            <div class="filter-group">
                <h4>Aesthetics</h4>
                <label><input type="checkbox"> Floral</label>
                <label><input type="checkbox"> Ocean</label>
            </div>
        </aside>

        <!-- Products -->
        <main>
            <div class="top-controls">
                <label><input type="checkbox"> Personalize</label>
                <select>
                    <option>Sort By</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                </select>
            </div>

            <div class="products">
                <?php for ($i = 0; $i < 9; $i++): ?>
                    <div class="product">
                        <img src="assets/images/ring1.png" alt="Product">
                        <h4>"Little Flower" Adjustable Ring</h4>
                        <div class="designer">Designed by Crescentia</div>
                        <div class="tags">
                            <span>Silver</span>
                            <span>Adjustable</span>
                        </div>
                        <div class="price">RM 33</div>
                    </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>

    <footer>
        <div class="footer-grid">
            <div>
                <h4>Info</h4>
                <a href="#">Terms & Conditions</a>
                <a href="#">Privacy & Policy</a>
                <a href="#">FAQ</a>
            </div>

            <div>
                <h4>Customer Service</h4>
                <p>013-8974568</p>
                <p>tink@gmail.com</p>
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
            © <?php echo date("Y"); ?> Tuk. All Rights Reserved
        </div>
    </footer>

</body>

</html>