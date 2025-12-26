<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ | Tink</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #F9F5F0; color: #0B2136; font-family: 'Lato', sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .container { max-width: 900px; margin: 60px auto; padding: 50px; background: #ffffff; border: 1px solid #e0e0e0; flex: 1; }
        h1 { font-family: 'Playfair Display', serif; text-align: center; font-size: 2.5rem; margin-bottom: 50px; color: #0B2136; }
        .faq-item { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #F9F5F0; }
        .question { font-family: 'Playfair Display', serif; font-weight: 600; color: #d4af37; font-size: 1.2rem; margin-bottom: 10px; }
        .answer { color: #333; font-size: 1rem; line-height: 1.6; }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <div class="faq-item">
            <p class="question">How much is shipping?</p>
            <p class="answer">Standard shipping is RM10. We offer <strong>FREE shipping</strong> for all orders over RM100.</p>
        </div>
        <div class="faq-item">
            <p class="question">Can I return customized jewelry?</p>
            <p class="answer">Due to their personalized nature, engraved items and "build-your-own" bracelets cannot be returned unless they are defective.</p>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>