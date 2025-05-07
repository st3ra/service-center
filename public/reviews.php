<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$reviews = $pdo->query('SELECT * FROM reviews')->fetchAll();
?>
<h1>Отзывы</h1>
<div class="row">
    <?php foreach ($reviews as $review): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $review['author']; ?></h5>
                    <p class="card-text"><?php echo $review['text']; ?></p>
                    <p class="card-text"><small class="text-muted"><?php echo $review['created_at']; ?></small></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php
require_once 'includes/footer.php';
?>