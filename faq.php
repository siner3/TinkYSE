<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>FAQ | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #F9F5F0;
            color: #0B2136;
            font-family: 'Lato', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 60px auto;
            padding: 50px;
            margin-top: 120px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            flex: 1;
        }


        /* --- HEADER (Compact) --- */
        .site-header {
            background-color: #203742;
            color: #fff;
            padding: 15px 0;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-nav {
            display: flex;
            gap: 25px;
        }

        .nav-link {
            color: #ccc;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #fff;
        }

        .header-right {
            display: flex;
            gap: 20px;
            color: white;
        }

        /* --- PAGE TITLE --- */
        .page-title {
            text-align: center;
            padding: 50px 0 30px;
            margin-top: 60px;
        }

        .page-title h1 {
            font-family: var(--font-serif);
            font-size: 3rem;
            color: var(--text-slate);
            font-weight: 400;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: #0B2136;
        }

        .faq-item {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #F9F5F0;
        }

        .question {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: #d4af37;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .answer {
            color: #333;
            font-size: 1rem;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <?php include 'components/header.php'; ?>
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <div class="faq-item">
            <p class="question">How much is shipping?</p>
            <p class="answer">Standard shipping is RM10. We offer <strong>FREE shipping</strong> for all orders over
                RM100.</p>
        </div>
        <div class="faq-item">
            <p class="question">Can I return customized jewelry?</p>
            <p class="answer">Due to their personalized nature, engraved items and "build-your-own" bracelets cannot be
                returned unless they are defective.</p>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
</body>

</html>