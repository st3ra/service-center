<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
?>

<!-- Page Title -->
<section class="page-title section">
    <div class="container">
        <div class="section-title">
            <span class="description-title">Что о нас говорят</span>
            <h2>Отзывы наших клиентов</h2>
        </div>
    </div>
</section><!-- /Page Title -->

<!-- Reviews Section -->
<section id="reviews" class="reviews-section section light-background">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                
                <?php
                // Получаем отзывы из базы данных
                $sql = "
                    SELECT 
                        r.text as review_text,
                        r.created_at as review_date,
                        u.name as user_name,
                        s.name as service_name
                    FROM reviews r
                    JOIN users u ON r.user_id = u.id
                    JOIN requests req ON r.request_id = req.id
                    JOIN services s ON req.service_id = s.id
                    WHERE r.text IS NOT NULL AND r.text != ''
                    ORDER BY r.created_at DESC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $reviews = $stmt->fetchAll();
                ?>

                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card" data-aos="fade-up">
                            <div class="review-header">
                                <div class="review-author">
                                    <i class="bi bi-person-circle"></i>
                                    <h4><?= htmlspecialchars($review['user_name']) ?></h4>
                                </div>
                                <div class="review-meta">
                                    <span class="service-name">Услуга: <?= htmlspecialchars($review['service_name']) ?></span>
                                    <span class="review-date"><?= date('d.m.Y', strtotime($review['review_date'])) ?></span>
                                </div>
                            </div>
                            <div class="review-body">
                                <p><?= htmlspecialchars($review['review_text']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">Отзывов пока нет.</div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>