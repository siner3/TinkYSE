<?php
session_start();
require_once 'config.php';

// 1. FETCH ALL DESIGNERS
$sql = "SELECT * FROM DESIGNER ORDER BY DESIGNER_NAME ASC";
$stmt = $pdo->query($sql);
$designers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CONFIG: Default Dummy Image (Use a local path like 'assets/img/placeholder.jpg' or a URL)
$dummyImage = "https://placehold.co/400x400/F5F5F5/CCCCCC?text=No+Image";
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
    <link rel="stylesheet" href="assets/css/designer.css">


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
                    // 2. FETCH TOP 4 ITEM IMAGES
                    $imgSql = "SELECT ITEM_IMAGE FROM ITEM WHERE DESIGNER_ID = ? AND ITEM_ACTIVE = 1 LIMIT 4";
                    $imgStmt = $pdo->prepare($imgSql);
                    $imgStmt->execute([$designer['DESIGNER_ID']]);
                    $fetchedImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

                    // 3. LOGIC: HANDLE PREVIEW IMAGES
                    // Filter out empty images and replace with dummy if needed
                    $previewImages = [];
                    foreach ($fetchedImages as $img) {
                        if (!empty($img)) {
                            $previewImages[] = $img;
                        } else {
                            $previewImages[] = $dummyImage;
                        }
                    }

                    // Count total for display text
                    $countSql = "SELECT COUNT(*) FROM ITEM WHERE DESIGNER_ID = ? AND ITEM_ACTIVE = 1";
                    $countStmt = $pdo->prepare($countSql);
                    $countStmt->execute([$designer['DESIGNER_ID']]);
                    $totalItems = $countStmt->fetchColumn();

                    // 4. LOGIC: LAYOUT CLASS (Grow Logic)
                    $count = count($previewImages);

                    // If 0 items, force 1 dummy image so the card isn't empty
                    if ($count == 0) {
                        $previewImages[] = $dummyImage;
                        $count = 1;
                    }

                    $layoutClass = "layout-" . $count; // e.g., 'layout-2'
                ?>
            <a href="designers-selected.php?id=<?= $designer['DESIGNER_ID'] ?>" class="designer-card">

                <div class="designer-preview <?= $layoutClass ?>">
                    <?php foreach ($previewImages as $imgSrc): ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Item Preview">
                    <?php endforeach; ?>
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
    <?php include 'components/footer.php'; ?>

</body>

</html>