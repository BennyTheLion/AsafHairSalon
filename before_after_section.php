<?php
require_once "config.php";
$conn = getDbConnection();
$items = $conn ? $conn->query("SELECT * FROM before_after WHERE is_active = 1 ORDER BY sort_order ASC, id DESC") : null;
$hasItems = $items && $items->num_rows > 0;
?>

<?php if ($hasItems): ?>
<section class="before-after" id="before-after">
    <div class="container">
        <h2>לפני ואחרי</h2>
        <p class="section-subtitle">תוצאות אמיתיות — ככה נראה ההבדל</p>

        <div class="ba-slider-wrapper">
            <?php $idx = 0; while ($ba = $items->fetch_assoc()): $idx++; ?>
            <div class="ba-card<?= $idx === 1 ? ' active' : '' ?>" data-index="<?= $idx ?>">
                <div class="ba-compare">
                    <div class="ba-after-wrap">
                        <img src="<?= htmlspecialchars($ba['after_image']) ?>" alt="אחרי: <?= htmlspecialchars($ba['title_he']) ?>" loading="lazy">
                        <div class="ba-overlay ba-overlay-after"><span>אחרי</span></div>
                    </div>
                    <div class="ba-before-wrap" style="width: 50%;">
                        <img src="<?= htmlspecialchars($ba['before_image']) ?>" alt="לפני: <?= htmlspecialchars($ba['title_he']) ?>" loading="lazy">
                        <div class="ba-overlay ba-overlay-before"><span>לפני</span></div>
                    </div>
                    <div class="ba-handle" style="right: 50%;">
                        <div class="ba-handle-line"></div>
                        <div class="ba-handle-circle">
                            <span class="ba-arrow-left">◀</span>
                            <span class="ba-arrow-right">▶</span>
                        </div>
                    </div>
                </div>
                <div class="ba-info">
                    <h3 class="ba-title"><?= htmlspecialchars($ba['title_he']) ?></h3>
                    <?php if (!empty($ba['description'])): ?>
                    <p class="ba-desc"><?= nl2br(htmlspecialchars($ba['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if ($items->num_rows > 1): ?>
        <div class="ba-dots">
            <?php for ($i = 1; $i <= $items->num_rows; $i++): ?>
            <button class="ba-dot<?= $i === 1 ? ' active' : '' ?>" data-go="<?= $i ?>" aria-label="עבור לתמונה <?= $i ?>"></button>
            <?php endfor; ?>
        </div>
        <div class="ba-nav-buttons">
            <button class="ba-nav ba-prev" aria-label="הקודם">▶</button>
            <button class="ba-nav ba-next" aria-label="הבא">◀</button>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
