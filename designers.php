<?php
session_start();
require_once 'config.php';

// 1. FETCH ALL DESIGNERS
$sql = "SELECT * FROM DESIGNER ORDER BY DESIGNER_NAME ASC";
$stmt = $pdo->query($sql);
$designers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Designers | TINK</title>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/catalog.css">
    <link rel="stylesheet" href="assets/css/designers.css">

    <style>
        .designers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px 100px;
        }

        .designers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 40px;
        }
    </style>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="page-title">
        <h1>Our Designers</h1>
    </div>

    <div class="designers-container">
        <div class="designers-grid">

            <?php if (count($designers) > 0): ?>
                <?php foreach ($designers as $designer):
                    // 2. FETCH TOP 4 ITEMS FOR THIS DESIGNER
                    $imgSql = "SELECT ITEM_IMAGE FROM ITEM WHERE DESIGNER_ID = ? AND ITEM_ACTIVE = 1 LIMIT 4";
                    $imgStmt = $pdo->prepare($imgSql);
                    $imgStmt->execute([$designer['DESIGNER_ID']]);
                    $previewImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

                    // Count total items
                    $countSql = "SELECT COUNT(*) FROM ITEM WHERE DESIGNER_ID = ? AND ITEM_ACTIVE = 1";
                    $countStmt = $pdo->prepare($countSql);
                    $countStmt->execute([$designer['DESIGNER_ID']]);
                    $totalItems = $countStmt->fetchColumn();
                ?>
                    <a href="designers-selected.php?id=<?= $designer['DESIGNER_ID'] ?>" class="designer-card">

                        <div class="designer-preview">
                            <?php
                            // Loop 4 times to fill the grid (even if empty)
                            for ($i = 0; $i < 4; $i++):
                                if (isset($previewImages[$i])): ?>
                                    <img src="<?= htmlspecialchars($previewImages[$i]) ?>" alt="Product Preview">
                                <?php else: ?>
                                    <div class="preview-placeholder"><i class="fa-solid fa-gem"></i></div>
                            <?php endif;
                            endfor; ?>
                        </div>

                        <div class="designer-info">
                            <div class="designer-name">
                                <?= htmlspecialchars($designer['DESIGNER_NAME']) ?>
                            </div>
                            <div class="item-count">
                                <?= $totalItems ?> Creations
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: #888;">No designers found.</div>
            <?php endif; ?>

        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>INFO</h4>
                <ul>
                    <li>Terms</li>
                    <li>Privacy</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>CONTACT</h4>
                <ul>
                    <li>013-8974568</li>
                    <li>tink@gmail.com</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>FOLLOW</h4>
                <ul>
                    <li>Facebook</li>
                    <li>Instagram</li>
                </ul>
            </div>
        </div>
        <div style="text-align:center; margin-top:40px; font-size:0.8rem; color:#888;">&copy; 2025 Tink.</div>
    </footer>

</body>

</html>