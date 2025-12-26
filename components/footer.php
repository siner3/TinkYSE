<<footer class="site-footer">
    <div class="footer-content">

        <div class="footer-col">
            <h4>Info</h4>
            <ul>
                <li><a href="terms.php">Terms & Conditions</a></li>
                <li><a href="privacy.php" target="_blank" rel="noopener noreferrer">Privacy & Policy</a></li>
                <li><a href="faq.php" target="_blank" rel="noopener noreferrer">FAQ</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Customer Service</h4>
            <p>
                <i class='bx bx-phone'></i>
                <span>013-8974568</span>
            </p>
            <p>
                <i class='bx bx-envelope'></i>
                <span>tink@gmail.com</span>
            </p>
        </div>

        <div class="footer-col">
            <h4>Follow Us</h4>
            <div class="social-links">
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Tink. All Rights Reserved
    </div>
</footer>

<style>
    /* Import Fonts if not already in header */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400&display=swap');

    .site-footer {
        background-color: #F9F5F0;
        /* Cream/Beige background from image */
        color: #0B2136;
        /* Dark Navy text */
        font-family: 'Lato', sans-serif;
        padding: 0;
        padding-top: 50px;
        margin-top: auto;
        /* Pushes footer to bottom if page is short */
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
        padding: 0 40px 60px 40px;
    }

    .footer-col h4 {
        font-family: 'Playfair Display', serif;
        /* Serif font for headings */
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        letter-spacing: 1px;
        color: #0B2136;
    }

    /* Links & Lists */
    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-col ul li {
        margin-bottom: 12px;
    }

    .footer-col ul li a {
        text-decoration: none;
        color: #0B2136;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
        transition: color 0.3s;
        font-family: 'Playfair Display', serif;
        /* Matching the serif look in image */
    }

    .footer-col ul li a:hover {
        color: #d4af37;
        /* Gold accent on hover */
    }

    /* Customer Service Text */
    .footer-col p {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-family: 'Playfair Display', serif;
        font-size: 0.95rem;
    }

    .footer-col i {
        font-size: 1.2rem;
    }

    /* Social Icons */
    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-links a {
        color: #0B2136;
        font-size: 1.5rem;
        transition: transform 0.3s, color 0.3s;
    }

    .social-links a:hover {
        color: #d4af37;
        transform: translateY(-3px);
    }

    /* Bottom Copyright Bar */
    .footer-bottom {
        background-color: #0B2136;
        /* Dark Navy background */
        color: #ffffff;
        text-align: center;
        padding: 15px 0;
        font-size: 0.75rem;
        font-family: 'Lato', sans-serif;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            /* Stack columns on mobile */
            text-align: center;
            gap: 30px;
        }

        .footer-col p,
        .social-links {
            justify-content: center;
        }
    }
</style>