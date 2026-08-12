<?php
require_once 'config.php';

$conn = getDbConnection();
$categories = [];

if ($conn) {

    // Load categories
    $catSql = "SELECT id, name_he FROM categories ORDER BY sort_order ASC, id ASC";
    $catResult = $conn->query($catSql);

    if ($catResult && $catResult->num_rows > 0) {
        while ($cat = $catResult->fetch_assoc()) {
            $categories[$cat['id']] = [
                'id' => (int)$cat['id'],
                'name' => $cat['name_he'],
                'services' => []
            ];
        }
    }

    // Load services
    $srvSql = "SELECT * FROM services ORDER BY category_id ASC, id ASC";
    $srvResult = $conn->query($srvSql);

    if ($srvResult && $srvResult->num_rows > 0) {
        while ($srv = $srvResult->fetch_assoc()) {
            $categoryId = (int)$srv['category_id'];

            if (isset($categories[$categoryId])) {
                $categories[$categoryId]['services'][] = $srv;
            }
        }
    }

    closeDbConnection();
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>השירותים שלנו</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f8f5f2;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px;
}

h1 {
    text-align: center;
    margin: 20px 0 30px;
    font-size: 32px;
}

/* CATEGORY ACCORDION */
.category {
    background: #fff;
    border-radius: 16px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
}

.category-header {
    background: linear-gradient(135deg, #c8a97e, #8b7355);
    color: #fff;
    padding: 18px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 20px;
    font-weight: bold;
    user-select: none;
}

.arrow {
    transition: transform 0.3s ease;
    font-size: 14px;
}

.category.active .arrow {
    transform: rotate(180deg);
}

/* SERVICES AREA */
.services {
    display: none;
    padding: 16px;
    background: #fafafa;
}

.category.active .services {
    display: block;
}

/* GRID OF CARDS */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
}

/* CARD */
.service-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.12);
}

/* IMAGE */
.service-image {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    background: #eee;
}

/* CONTENT */
.service-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.service-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 8px;
    line-height: 1.4;
}

.service-description {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 12px;
    flex: 1;
}

/* META */
.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: auto;
}

.duration {
    font-size: 14px;
    color: #777;
}

.price {
    font-size: 20px;
    font-weight: bold;
    color: #c8a97e;
}

/* NO SERVICES */
.no-services {
    text-align: center;
    color: #888;
    padding: 20px;
}

/* MOBILE */
@media (max-width: 768px) {
    .container {
        padding: 12px;
    }

    h1 {
        font-size: 26px;
        margin: 15px 0 20px;
    }

    .category-header {
        padding: 16px;
        font-size: 18px;
    }

    .services {
        padding: 12px;
    }

    .services-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .service-content {
        padding: 14px;
    }

    .service-title {
        font-size: 17px;
    }

    .price {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .service-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .service-image {
        aspect-ratio: 1 / 1;
    }
}
</style>
</head>
<body>

<div class="container">
    <h2>השירותים שלנו</h2>

    <?php if (empty($categories)): ?>
        <p style="text-align:center;">לא נמצאו קטגוריות.</p>
    <?php else: ?>

        <?php foreach ($categories as $cat): ?>
            <div class="category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="arrow">▼</span>
                </div>

                <div class="services">

                    <?php if (empty($cat['services'])): ?>
                        <div class="no-services">אין שירותים בקטגוריה זו.</div>
                    <?php else: ?>

                        <div class="services-grid">
                            <?php foreach ($cat['services'] as $service): ?>
                                <?php
                                    $image = !empty($service['image_url'])
                                        ? $service['image_url']
                                        : 'https://via.placeholder.com/600x450?text=Service';

                                    $title = $service['title'] ?? $service['name'] ?? 'ללא שם';
                                    $description = $service['short_description'] ?? '';
                                    $duration = isset($service['duration']) ? (int)$service['duration'] : 0;
                                    $price = $service['base_price']
                                        ?? $service['price']
                                        ?? '';
                                ?>

                                <div class="service-card">
                                    <img
                                        class="service-image"
                                        src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                        loading="lazy"
                                    >

                                    <div class="service-content">
                                        <div class="service-title">
                                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                        </div>

                                        <?php if (!empty($description)): ?>
                                            <div class="service-description">
                                                <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="service-meta">
                                            <?php if ($duration > 0): ?>
                                                <div class="duration">⏱ <?= $duration ?> דקות</div>
                                            <?php endif; ?>

                                            <?php if ($price !== ''): ?>
                                                <div class="price">
                                                    ₪<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
function toggleCategory(header) {
    const category = header.parentElement;
    category.classList.toggle('active');
}
</script>

</body>
</html>