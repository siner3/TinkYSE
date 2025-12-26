<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cust-login.css">

    <style>
        .login-form-wrapper {
            align-items: flex-start;
            padding-top: 40px;
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
            <img src="assets/images/login.png" alt="Tink Jewelry Login">
        </div>

        <div class="login-form-wrapper">
            <div class="login-form">
                <h1>Welcome Back</h1>
                <p class="subtitle">Please login to manage your orders.</p>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <form action="login_process.php" method="POST">
                    <fieldset class="input-group">
                        <legend>Email Address</legend>
                        <input type="email" name="email" required>
                    </fieldset>

                    <fieldset class="input-group">
                        <legend>Password</legend>
                        <input type="password" name="password" required>
                    </fieldset>

                    <button type="submit" class="login-btn">Login</button>
                </form>

                <div style="text-align: center; margin-top: 15px; font-size: 0.9rem;">
                    Don't have an account? <a href="signup.php" style="color: #333; font-weight: 600;">Sign Up</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

</body>

</html>