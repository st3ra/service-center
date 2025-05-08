<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$categories = $pdo->query('SELECT * FROM categories')->fetchAll();
?>
<h1>Каталог услуг</h1>
<ul class="nav nav-tabs" id="servicesTab" role="tablist">
    <?php foreach ($categories as $index => $category): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" id="tab-<?php echo $category['id']; ?>" data-bs-toggle="tab" data-bs-target="#category-<?php echo $category['id']; ?>" type="button" role="tab" aria-controls="category-<?php echo $category['id']; ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"><?php echo $category['name']; ?></button>
        </li>
    <?php endforeach; ?>
</ul>
<div class="tab-content" id="servicesTabContent">
    <?php foreach ($categories as $index => $category): ?>
        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" id="category-<?php echo $category['id']; ?>" role="tabpanel" aria-labelledby="tab-<?php echo $category['id']; ?>">
            <div class="row">
                <?php
                $stmt = $pdo->prepare('SELECT * FROM services WHERE category_id = ?');
                $stmt->execute([$category['id']]);
                $services = $stmt->fetchAll();
                foreach ($services as $service):
                    $image_path = $service['image_path'] ?: 'images/services/placeholder.jpg';
                    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_path;
                    error_log("Checking image: $full_path");
                    if (!file_exists($full_path)) {
                        error_log("Image not found: $full_path");
                        $image_path = 'images/services/placeholder.jpg';
                    }
                ?>
                    <div class="col-md-4">
                        <div class="card">
                            <img src="/<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo $service['name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $service['name']; ?></h5>
                                <p class="card-text"><?php echo $service['description']; ?></p>
                                <p class="card-text"><strong>Цена: <?php echo $service['price']; ?> руб.</strong></p>
                                <a href="form.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">Записаться</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php
require_once 'includes/footer.php';
?>