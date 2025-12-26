<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sign Up | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cust-login.css">

    <style>
        .login-form-wrapper {
            align-items: flex-start;
            padding-top: 40px;
            overflow-y: auto;
        }

        .error-message {
            color: #d9534f;
            background-color: #fdf7f7;
            border: 1px solid #e0b4b4;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-align: center;
        }

        fieldset.input-group {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px 12px;
            margin-bottom: 15px;
        }

        fieldset.input-group legend {
            font-size: 0.85rem;
            padding: 0 5px;
            color: #1a1a1a;
            font-weight: 500;
        }

        fieldset.input-group input {
            border: none;
            width: 100%;
            outline: none;
            background: transparent;
            padding: 5px 0;
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="product-page">

    <?php include 'components/header.php'; ?>

    <div class="login-page">
        <div class="login-image">
            <img src="assets/images/signup.png" alt="Join Tink Jewelry" onerror="this.src='assets/images/login.png'">
        </div>

        <div class="login-form-wrapper">
            <div class="login-form">
                <h1>Create Account</h1>
                <p class="subtitle">Join our community of jewelry lovers.</p>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <form action="signup_process.php" method="POST">

                    <fieldset class="input-group">
                        <legend>Full Name</legend>
                        <input type="text" name="name" required>
                    </fieldset>

                    <fieldset class="input-group">
                        <legend>Email Address</legend>
                        <input type="email" name="email" required>
                    </fieldset>

                    <fieldset class="input-group">
                        <legend>Phone Number</legend>
                        <input type="tel" name="phone" required>
                    </fieldset>

                    <fieldset class="input-group">
                        <legend>Password</legend>
                        <input type="password" name="password" required>
                    </fieldset>

                    <fieldset class="input-group">
                        <legend>Confirm Password</legend>
                        <input type="password" name="confirm_password" required>
                    </fieldset>

                    <p style="font-size: 0.8rem; color: #666; margin-bottom: 15px; text-align: center;">
                        By registering, you agree to our
                        <a href="terms.php" target="_blank" style="color: #d4af37; text-decoration: underline;">Terms &
                            Conditions</a>.
                    </p>

                    <button type="submit" class="login-btn">Register</button>
                </form>

                <div style="text-align: center; margin-top: 15px; font-size: 0.9rem;">
                    Already have an account? <a href="login.php" style="color: #333; font-weight: 600;">Login</a>
                </div>
            </div>
        </div>
    </div>

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