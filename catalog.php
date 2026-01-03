<?php
session_start();
require_once 'config.php';

// --- 1. INITIALIZE & DETERMINE MODE ---
// FIX: Added LEFT JOIN to fetch the actual DESIGNER_NAME
$sql = "SELECT ITEM.*, DESIGNER.DESIGNER_NAME 
        FROM ITEM 
        LEFT JOIN DESIGNER ON ITEM.DESIGNER_ID = DESIGNER.DESIGNER_ID 
        WHERE ITEM_ACTIVE = 1";

$params = [];
$title_text = "Catalog";

if (!empty($_GET['search'])) {
    // SEARCH MODE
    $search = trim($_GET['search']);
    $sql .= " AND (ITEM_NAME LIKE ? OR ITEM_DESCRIPTION LIKE ? OR ITEM_TAGS LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $title_text = "Search: \"" . htmlspecialchars($search) . "\"";
    $category = 'Search';
} else {
    // CATEGORY MODE
    $category = isset($_GET['cat']) ? $_GET['cat'] : 'Necklaces';
    $allowed = ['Necklaces', 'Bracelets', 'Earrings', 'Rings', 'Charms'];
    if (!in_array($category, $allowed)) $category = 'Necklaces';

    $sql .= " AND ITEM_CATEGORY = ?";
    $params[] = $category;
    $title_text = strtoupper($category);
}

// --- 2. APPLY SIDEBAR FILTERS ---
$active_filters = [];
$filter_keys = ['gender', 'style', 'material', 'designer', 'aesthetics'];

foreach ($filter_keys as $key) {
    if (!empty($_GET[$key])) {
        $sub_clauses = [];
        foreach ($_GET[$key] as $val) {
            // Checks Tags, Material column, or Designer Name
            $sub_clauses[] = "(ITEM_TAGS LIKE ? OR ITEM_MATERIAL LIKE ? OR DESIGNER.DESIGNER_NAME = ?)";
            $params[] = "%$val%";
            $params[] = "%$val%";
            $params[] = $val;

            $active_filters[] = ['key' => $key, 'value' => $val];
        }
        if (!empty($sub_clauses)) {
            $sql .= " AND (" . implode(' OR ', $sub_clauses) . ")";
        }
    }
}

// --- NEW: PERSONALIZE FILTER ---
// This checks the IS_ENGRAVABLE column in your database
if (isset($_GET['personalize'])) {
    $sql .= " AND ITEM.IS_ENGRAVABLE = 1";
}

// --- 3. APPLY SORTING ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'best-selling';
switch ($sort) {
    case 'price-low':
        $sql .= " ORDER BY ITEM_PRICE ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY ITEM_PRICE DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY ITEM_ID DESC";
        break;
    default: // best-selling
        $sql .= " ORDER BY ITEM_ID DESC";
        break;
}

// --- 4. EXECUTE QUERY ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 5. GROUP VARIANTS ---
$grouped_products = [];
foreach ($raw_items as $item) {
    $key = $item['PARENT_ID'] ? $item['PARENT_ID'] : $item['ITEM_ID'];
    if (!isset($grouped_products[$key])) {
        $grouped_products[$key] = ['base' => $item, 'variants' => []];
    }
    $grouped_products[$key]['variants'][] = $item;
}

// --- 6. HELPER FUNCTIONS ---
function getRemoveUrl($key, $value)
{
    $params = $_GET;
    if (isset($params[$key]) && is_array($params[$key])) {
        $index = array_search($value, $params[$key]);
        if ($index !== false) unset($params[$key][$index]);
    }
    if ($key === 'search') unset($params['search']);
    if ($key === 'personalize') unset($params['personalize']);
    return '?' . http_build_query($params);
}

// Sidebar Options
$sidebar_filters = [
    'Gender'     => ['Women', 'Men', 'Unisex'],
    'Style'      => ['Minimalist', 'Statement', 'Layered', 'Classic', 'Luxury'],
    'Material'   => ['Silver', 'Gold', 'Rose Gold', 'Pearl'],
    'Aesthetics' => ['Vintage', 'Boho', 'Modern', 'Coquette']
];

// Sort Display Labels
$sort_labels = [
    'best-selling' => 'Sort By: Best Selling',
    'price-low' => 'Price: Low to High',
    'price-high' => 'Price: High to Low',
    'newest' => 'Newest First'
];
$current_sort_label = isset($sort_labels[$sort]) ? $sort_labels[$sort] : $sort_labels['best-selling'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title_text ?> | TINK</title>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/catalog.css">

    <script>
    // Define functions early so they're available for inline event handlers
    function toggleFilter(element) {
        element.parentElement.classList.toggle('active');
    }

    function updateCard(el, id) {
        const img = document.getElementById('img-' + id);
        const price = document.getElementById('price-' + id);
        const title = document.getElementById('title-' + id);

        // Update active swatch state
        const swatches = el.parentElement.querySelectorAll('.swatch');
        swatches.forEach(s => s.classList.remove('active'));
        el.classList.add('active');

        if (img && el.dataset.image) img.src = el.dataset.image;
        if (price && el.dataset.price) price.innerText = el.dataset.price;
        if (title && el.dataset.name) title.innerText = el.dataset.name;
    }

    // Sort dropdown handler
    function handleSort(value) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', value);
        window.location.search = urlParams.toString();
    }

    // Personalize checkbox handler
    function handlePersonalize(checked) {
        const urlParams = new URLSearchParams(window.location.search);
        if (checked) {
            urlParams.set('personalize', '1');
        } else {
            urlParams.delete('personalize');
        }
        window.location.search = urlParams.toString();
    }
    </script>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <div class="page-title">
        <h1><?= $title_text ?></h1>
    </div>

    <div class="catalog-controls">
        <label style="cursor:pointer; display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="personalizeFilter" onchange="handlePersonalize(this.checked)"
                <?php echo isset($_GET['personalize']) ? 'checked' : ''; ?>>
            Personalize
        </label>

        <select id="sortDropdown" class="sort-select" onchange="handleSort(this.value)">
            <option value="best-selling" <?= $sort == 'best-selling' ? 'selected' : '' ?>>Sort By: Best Selling</option>
            <option value="price-low" <?= $sort == 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price-high" <?= $sort == 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
        </select>
    </div>

    <div class="catalog-layout">

        <aside class="sidebar">
            <div class="sidebar-header">Filter</div>
            <form id="filterForm" method="GET" action="catalog.php">

                <?php if (!empty($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                <?php else: ?>
                <input type="hidden" name="cat" value="<?= htmlspecialchars($category) ?>">
                <?php endif; ?>

                <?php if (isset($_GET['personalize'])): ?>
                <input type="hidden" name="personalize" value="1">
                <?php endif; ?>

                <?php foreach ($sidebar_filters as $title => $opts):
                    $key = strtolower($title);
                    $isOpen = (isset($_GET[$key]) && !empty($_GET[$key])) ? 'active' : '';
                ?>
                <div class="filter-group <?= $isOpen ?>">
                    <div class="filter-title" onclick="toggleFilter(this)">
                        <?= $title ?> <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="filter-options">
                        <?php foreach ($opts as $opt):
                                $isChecked = (isset($_GET[$key]) && in_array($opt, $_GET[$key])) ? 'checked' : '';
                            ?>
                        <label>
                            <input type="checkbox" name="<?= $key ?>[]" value="<?= $opt ?>" <?= $isChecked ?>>
                            <?= $opt ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-apply">Apply Filters</button>
            </form>
        </aside>

        <div style="width: 100%;">

            <?php if (!empty($active_filters) || !empty($_GET['search']) || isset($_GET['personalize'])): ?>
            <div class="active-filters">
                <?php if (!empty($_GET['search'])): ?>
                <a href="<?= getRemoveUrl('search', $_GET['search']) ?>" class="filter-chip">
                    Search: <?= htmlspecialchars($_GET['search']) ?> <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>

                <?php if (isset($_GET['personalize'])): ?>
                <a href="<?= getRemoveUrl('personalize', '1') ?>" class="filter-chip">
                    Personalizable <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>

                <?php foreach ($active_filters as $filter): ?>
                <a href="<?= getRemoveUrl($filter['key'], $filter['value']) ?>" class="filter-chip">
                    <?= htmlspecialchars($filter['value']) ?> <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endforeach; ?>

                <a href="catalog.php?cat=<?= htmlspecialchars($category) ?>" class="clear-all">Clear All</a>
            </div>
            <?php endif; ?>

            <main class="product-grid">
                <?php if (empty($grouped_products)): ?>
                <div style="grid-column:1/-1; text-align:center; padding:60px; color:#888;">
                    <i class="fa-regular fa-folder-open" style="font-size:2.5rem; margin-bottom:15px;"></i>
                    <p>No products found matching your selection.</p>
                </div>
                <?php else: ?>
                <?php foreach ($grouped_products as $group):
                        $base = $group['base'];
                        $variants = $group['variants'];

                        // Image Fix
                        $imgUrl = !empty($base['ITEM_IMAGE']) ? ltrim($base['ITEM_IMAGE'], '/') : '';
                    ?>
                <div class="product-card">
                    <div class="image-wrapper">
                        <a href="product_detail.php?id=<?= $base['ITEM_ID'] ?>">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" id="img-<?= $base['ITEM_ID'] ?>"
                                alt="<?= htmlspecialchars($base['ITEM_NAME']) ?>">
                        </a>

                        <?php if (count($variants) > 1): ?>
                        <div class="swatches">
                            <?php foreach ($variants as $v):
                                            $mat = strtolower($v['ITEM_MATERIAL']);
                                            // Determine swatch color - check rose gold first (before gold)
                                            $cls = 'silver';
                                            if (strpos($mat, 'rose') !== false) {
                                                $cls = 'rose';
                                            } elseif (strpos($mat, 'gold') !== false && strpos($mat, 'sterling') === false) {
                                                $cls = 'gold';
                                            } elseif (strpos($mat, 'gold plated') !== false || strpos($mat, '14k gold') !== false || strpos($mat, '18k gold') !== false) {
                                                $cls = 'gold';
                                            }

                                            // Variant Image Fix
                                            $vImgUrl = !empty($v['ITEM_IMAGE']) ? ltrim($v['ITEM_IMAGE'], '/') : '';
                                        ?>
                            <span class="swatch <?= $cls ?>" onmouseover="updateCard(this, '<?= $base['ITEM_ID'] ?>')"
                                data-image="<?= htmlspecialchars($vImgUrl) ?>"
                                data-price="RM <?= number_format($v['ITEM_PRICE'], 2) ?>"
                                data-name="<?= htmlspecialchars($v['ITEM_NAME']) ?>" data-id="<?= $v['ITEM_ID'] ?>">
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-name" id="title-<?= $base['ITEM_ID'] ?>">
                        <?= htmlspecialchars($base['ITEM_NAME']) ?>
                    </div>

                    <div class="designer">
                        DESIGNED BY: <?= htmlspecialchars($base['DESIGNER_NAME'] ?? 'TINK Studio') ?>
                    </div>

                    <div class="price" id="price-<?= $base['ITEM_ID'] ?>">
                        RM <?= number_format($base['ITEM_PRICE'], 2) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

</body>

</html>