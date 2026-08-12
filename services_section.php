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

<section class="section" id="services">
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
</section>

<script>
function toggleCategory(header) {
    const category = header.parentElement;
    category.classList.toggle('active');
}
</script>
