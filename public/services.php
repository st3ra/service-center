<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Получаем все категории
try {
    $categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Обработка ошибки, если не удалось получить категории
    $categories = [];
    // Можно добавить логирование ошибки $e->getMessage()
}
?>

<main class="main">

  <!-- Page Title -->
  <div class="container section-title" data-aos="fade-up">
    <span class="description-title">Каталог</span>
    <h2>Наши услуги</h2>
  </div><!-- End Page Title -->

  <!-- Services Section -->
  <section id="services-list" class="services section">
    <div class="container">

      <?php if (empty($categories)): ?>
        <div class="alert alert-warning" role="alert">
          В данный момент список услуг пуст. Пожалуйста, зайдите позже.
        </div>
      <?php else: ?>
        
        <!-- Tabs Nav -->
        <ul class="nav nav-pills justify-content-center" id="services-tab" role="tablist" data-aos="fade-up" data-aos-delay="100">
          <?php foreach ($categories as $index => $category): ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" id="tab-<?= $category['id'] ?>" data-bs-toggle="tab" data-bs-target="#category-<?= $category['id'] ?>" type="button" role="tab" aria-controls="category-<?= $category['id'] ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"><?= htmlspecialchars($category['name']) ?></button>
            </li>
          <?php endforeach; ?>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content pt-5" id="services-tab-content" data-aos="fade-up" data-aos-delay="200">
          <?php foreach ($categories as $index => $category): ?>
            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="category-<?= $category['id'] ?>" role="tabpanel" aria-labelledby="tab-<?= $category['id'] ?>">
              
              <?php
              $stmt = $pdo->prepare('SELECT * FROM services WHERE category_id = ?');
              $stmt->execute([$category['id']]);
              $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
              ?>

              <?php if (empty($services)): ?>
                <p>В этой категории пока нет услуг.</p>
              <?php else: ?>
                <div class="row gy-4">
                  <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6">
                      <div class="service-item-with-image">
                        <?php 
                          $placeholder = 'images/services/placeholder.jpg';
                          $image_path = $placeholder;
                          if (!empty($service['image_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($service['image_path'], '/'))) {
                              $image_path = ltrim($service['image_path'], '/');
                          }
                        ?>
                        <img src="/<?= htmlspecialchars($image_path) ?>" class="service-image" alt="<?= htmlspecialchars($service['name']) ?>">
                        <div class="service-content">
                          <h3><?= htmlspecialchars($service['name']) ?></h3>
                          <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                          <p class="service-price"><strong>Цена: <?= htmlspecialchars($service['price']) ?> руб.</strong></p>
                          <a href="form.php?service_id=<?= $service['id'] ?>" class="secondary-btn mt-auto">Записаться <i class="bi bi-chevron-right"></i></a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </section><!-- /Services Section -->

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Функция для активации вкладки по хэшу
  const activateTabFromHash = () => {
    const hash = window.location.hash;
    if (hash) {
      const triggerEl = document.querySelector(`.nav-pills .nav-link[data-bs-target="${hash}"]`);
      if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
        
        const tabsContainer = document.getElementById('services-tab');
        if(tabsContainer) {
            // Плавная прокрутка только если элемент не виден полностью
            const rect = tabsContainer.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                 tabsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
      }
    }
  };

  // Вызываем функцию при первоначальной загрузке
  activateTabFromHash();

  // И добавляем слушатель на изменение хэша
  window.addEventListener('hashchange', activateTabFromHash);
});
</script>

<?php
require_once 'includes/footer.php';
?>