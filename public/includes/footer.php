</main>

<footer id="footer" class="footer">

  <div class="container footer-top">
    <div class="row gy-4">
      <div class="col-lg-4 col-md-12 footer-about">
        <a href="/" class="logo d-flex align-items-center">
          <span class="sitename">Сервисный центр</span>
        </a>
        <p>Мы предлагаем качественный ремонт техники по доступным ценам. Наша цель — быстро и надежно вернуть вашу технику к жизни.</p>
        <div class="social-links d-flex mt-4">
          <a href="#"><i class="bi bi-youtube"></i></a>
          <a href="#"><i class="bi bi-telegram"></i></a>
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-6 footer-links">
        <h4>Навигация</h4>
        <ul>
          <li><a href="/">Главная</a></li>
          <li><a href="/about.php">О нас</a></li>
          <li><a href="/services.php">Услуги</a></li>
          <li><a href="/contacts.php">Контакты</a></li>
          <li><a href="/reviews.php">Отзывы</a></li>
        </ul>
      </div>

      <div class="col-lg-2 col-6 footer-links">
        <h4>Наши услуги</h4>
        <?php
            // Динамическая загрузка категорий услуг
            try {
                if (!isset($pdo)) {
                    require_once 'db.php';
                }
                $stmt = $pdo->query("SELECT * FROM categories ORDER BY name LIMIT 5");
                $categories = $stmt->fetchAll();
                if ($categories) {
                    echo '<ul>';
                    foreach ($categories as $category) {
                        echo '<li><a href="/services.php#category-' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a></li>';
                    }
                    echo '</ul>';
                }
            } catch (PDOException $e) {
                // Можно вывести ошибку или заглушку
                echo '<ul><li><a href="/services.php">Все услуги</a></li></ul>';
            }
        ?>
      </div>

      <div class="col-lg-4 col-md-12 footer-contact text-center text-md-start">
        <h4>Контакты</h4>
        <p>г. Москва, ул. Примерная, д. 1, офис 101</p>
        <p class="mt-4">
          <strong>Телефон:</strong> <span>+7 (495) 123-45-67</span><br>
          <strong>Email:</strong> <span>info@service-center.ru</span>
        </p>
      </div>

    </div>
  </div>

  <div class="container copyright text-center mt-4">
    <p>© <span>Copyright</span> <strong class="px-1 sitename">Сервисный центр</strong> <span>All Rights Reserved</span></p>
  </div>

</footer>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Preloader -->
<div id="preloader"></div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Vendor JS Files -->
<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/aos/aos.js"></script>
<script src="/assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="/assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="/assets/vendor/glightbox/js/glightbox.min.js"></script>

<!-- Main JS File -->
<script src="/assets/js/main.js"></script>

<!-- Custom JS -->
<script src="/assets/js/auth.js"></script>

</body>

</html>