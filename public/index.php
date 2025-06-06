<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
?>

<!-- Hero Section -->
<section id="hero" class="hero section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 hero-content" data-aos="fade-up" data-aos-delay="100">
        <h1 class="title">
          Наша магия вернет к жизни вашу технику
        </h1>

        <div class="description">
<p>Чиним все, что включается в розетку. А если не включается — починим и розетку. Цены не кусаются, в отличие от вашего робота-пылесоса.</p>
        </div>

        <div class="hero-buttons">
          <a href="/services.php" class="primary-btn">
            Узнать, на что мы способны
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>
      </div>

      <div class="col-lg-6 hero-visual" data-aos="fade-up" data-aos-delay="200">
        <div class="image-wrapper">
          <img src="images/misc/misc-square-16.webp" alt="Creative Design" class="main-image">

          <div class="floating-element top-left">
            <i class="bi bi-star-fill"></i>
          </div>

          <div class="floating-element bottom-right">
            <i class="bi bi-circle-fill"></i>
          </div>

          <div class="experience-badge">
            <span class="years">10+</span>
            <span class="text">Лет борьбы с энтропией</span>
          </div>
        </div>

        <div class="client-counter">
          <div class="counter-number">
            <span>750+</span>
          </div>
          <div class="counter-text">
            <span>Устройств спасено от полета в окно</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section><!-- /Hero Section -->

<!-- About Section -->
<section id="about" class="about section light-background">

  <!-- Section Title -->
  <div class="container section-title" data-aos="fade-up">
    <span class="description-title">Кто мы такие?</span>
    <h2>Наша тайная организация</h2>
    <p>Мы — группа энтузиастов, которые верят, что у каждой микросхемы есть душа. Наша миссия — договориться с ней и заставить работать.</p>
  </div><!-- End Section Title -->

  <div class="container" data-aos="fade-up" data-aos-delay="100">

    <div class="row align-items-center gy-5">
      <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
        <div class="about-image-wrapper position-relative">
          <img src="images/about/about-8.webp" alt="About Us" class="img-fluid rounded-4">
          <div class="mission-card">
            <div class="mission-icon">
              <i class="bi bi-lightbulb"></i>
            </div>
            <div class="mission-content">
              <h4>Наше кредо</h4>
              <p>Мы находим креативные способы убедить вашу кофеварку, что она еще не готова на пенсию. Экономим ваши деньги, чтобы вы могли потратить их на печеньки.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
        <div class="about-content ps-lg-4">
          <div class="tag-badge" data-aos="fade-up" data-aos-delay="100">О НАС, ГРЕШНЫХ</div>
          <h2 class="mb-4" data-aos="fade-up" data-aos-delay="200">Как мы докатились до такой жизни (и преуспели)</h2>

          <div class="about-info" data-aos="fade-up" data-aos-delay="300">
            <p>Наш метод прост: немного магии, щепотка отчаяния и много-много кофе. Мы неустанно боремся с техно-полтергейстами, чтобы вы могли спать спокойно.</p>
            <p>Мы постоянно смотрим обучающие видео на YouTube, чтобы быть в курсе, как починить новейший умный холодильник, который решил стать поэтом и пишет грустные хокку на дисплее.</p>
          </div>

          <h4 class="values-title mt-4 mb-3" data-aos="fade-up" data-aos-delay="400">Наши суперсилы</h4>

          <div class="values-list" data-aos="fade-up" data-aos-delay="500">
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Не уроним (наверное)</span>
            </div>
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Работает дольше гарантии</span>
            </div>
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Изолента — наш друг</span>
            </div>
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Если что, мы не виноваты</span>
            </div>
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Выслушаем ваш ноутбук</span>
            </div>
            <div class="value-item">
              <div class="value-icon"><i class="bi bi-check2"></i></div>
              <span class="value-text">Скоро починим и вас</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</section><!-- /About Section -->

<!-- Stats Section -->
<section id="stats" class="stats section">

  <div class="container" data-aos="fade-up" data-aos-delay="100">

    <?php
    try {
        // Счастливые клиенты (уникальные пользователи с завершенными заявками)
        $stmt_clients = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM requests WHERE status = 'completed'");
        $happy_clients = $stmt_clients->fetchColumn() ?: 0;

        // Выполненные заявки
        $stmt_completed = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE status = 'completed'");
        $completed_requests = $stmt_completed->fetchColumn() ?: 0;

        // Сотрудники (все, кто не обычный пользователь)
        $stmt_team = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'user'");
        $team_members_count = $stmt_team->fetchColumn() ?: 0;

        // Всего видов услуг
        $stmt_services = $pdo->query("SELECT COUNT(*) as count FROM services");
        $total_services = $stmt_services->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        // Запасные значения на случай ошибки БД
        error_log("Stats query failed: " . $e->getMessage());
        $happy_clients = 750;
        $completed_requests = 1200;
        $team_members_count = 15;
        $total_services = 20;
    }
    ?>

    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="stats-wrapper">
          <div class="stats-item" data-aos="zoom-in" data-aos-delay="150">
            <div class="icon-wrapper">
              <i class="bi bi-emoji-smile"></i>
            </div>
            <span data-purecounter-start="0" data-purecounter-end="<?= $happy_clients ?>" data-purecounter-duration="1" class="purecounter"></span>
            <p>Довольных владельцев</p>
          </div><!-- End Stats Item -->

          <div class="stats-item" data-aos="zoom-in" data-aos-delay="200">
            <div class="icon-wrapper">
              <i class="bi bi-journal-richtext"></i>
            </div>
            <span data-purecounter-start="0" data-purecounter-end="<?= $completed_requests ?>" data-purecounter-duration="1" class="purecounter"></span>
            <p>Побед над техникой</p>
          </div><!-- End Stats Item -->

          <div class="stats-item" data-aos="zoom-in" data-aos-delay="250">
            <div class="icon-wrapper">
              <i class="bi bi-tools"></i>
            </div>
            <span data-purecounter-start="0" data-purecounter-end="<?= $total_services ?>" data-purecounter-duration="1" class="purecounter"></span>
            <p>Способов удивить вас</p>
          </div><!-- End Stats Item -->

          <div class="stats-item" data-aos="zoom-in" data-aos-delay="300">
            <div class="icon-wrapper">
              <i class="bi bi-people"></i>
            </div>
            <span data-purecounter-start="0" data-purecounter-end="<?= $team_members_count ?>" data-purecounter-duration="1" class="purecounter"></span>
            <p>Героев с паяльниками</p>
          </div><!-- End Stats Item -->
        </div>
      </div>
    </div>

  </div>

</section><!-- /Stats Section -->

<!-- Services Section -->
<section id="services" class="services section">

  <!-- Section Title -->
  <div class="container section-title" data-aos="fade-up">
    <span class="description-title">Наши фокусы</span>
    <h2>Что мы умеем</h2>
    <p>От замены батарейки в пульте до переговоров с искусственным интеллектом вашего тостера. Вот малая часть наших талантов.</p>
  </div><!-- End Section Title -->

  <div class="container" data-aos="fade-up" data-aos-delay="100">

    <?php
    $sql = "
        SELECT s.id, s.name, s.description, s.image_path, COUNT(r.service_id) as request_count
        FROM services s
        LEFT JOIN requests r ON s.id = r.service_id
        GROUP BY s.id
        ORDER BY request_count DESC
        LIMIT 6;
    ";
    
    try {
        $stmt = $pdo->query($sql);
        $popular_services = $stmt->fetchAll();
    } catch (PDOException $e) {
        $popular_services = [];
        error_log("Popular services query failed: " . $e->getMessage());
    }
    ?>

    <div class="row g-4">
      <?php if (!empty($popular_services)): ?>
        <?php 
          $icons = ['bi-lightbulb', 'bi-bar-chart', 'bi-people', 'bi-graph-up-arrow', 'bi-tools', 'bi-pc-display'];
          $icon_index = 0;
        ?>
        <?php foreach ($popular_services as $service): ?>
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= 100 * ($icon_index + 1) ?>">
            <div class="service-card">
              <div class="service-card-inner">
                <div class="service-icon">
                  <i class="bi <?= $icons[$icon_index % count($icons)] ?>"></i>
                </div>
                <h3><?= htmlspecialchars($service['name']) ?></h3>
                <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
              </div>
            </div>
          </div>
          <?php $icon_index++; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Информация об услугах временно недоступна.</p>
      <?php endif; ?>
    </div>

  </div>

</section><!-- /Services Section -->

<!-- Faq Section -->
<section id="faq" class="faq section">

  <!-- Section Title -->
  <div class="container section-title" data-aos="fade-up">
    <span class="description-title">Отвечаем на невысказанное</span>
    <h2>Вопросы из зала</h2>
  </div><!-- End Section Title -->

  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="faq-container">
          <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
            <h3><span class="num">1.</span> Как долго мне страдать без моего гаджета?</h3>
            <div class="faq-content">
              <p>Зависит от того, насколько он на вас обиделся. Обычно от 'минуточку' до 'заказываем деталь с Марса'. Но мы сообщим.</p>
            </div>
            <i class="bi bi-chevron-down faq-toggle"></i>
          </div><!-- End Faq item-->

          <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
            <h3><span class="num">2.</span> А если оно снова сломается?</h3>
            <div class="faq-content">
              <p>Даем гарантию. Если что, у нас есть шаманский бубен для особо упрямых случаев. Повторный ремонт с его использованием — бесплатно.</p>
            </div>
            <i class="bi bi-chevron-down faq-toggle"></i>
          </div><!-- End Faq item-->

          <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
            <h3><span class="num">3.</span> Сколько стоит просто посмотреть?</h3>
            <div class="faq-content">
              <p>Бесплатно, если остаетесь на ремонт. Иначе — символическая плата на кофе нашим нервным мастерам.</p>
            </div>
            <i class="bi bi-chevron-down faq-toggle"></i>
          </div><!-- End Faq item-->

          <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
            <h3><span class="num">4.</span> Вы поставите туда что-то приличное?</h3>
            <div class="faq-content">
              <p>Ставим родные детали. Если их уже не производят, найдем достойную замену или уговорим старую поработать еще.</p>
            </div>
            <i class="bi bi-chevron-down faq-toggle"></i>
          </div><!-- End Faq item-->

          <div class="faq-item" data-aos="fade-up" data-aos-delay="600">
            <h3><span class="num">5.</span> Что если наступит техно-апокалипсис 2.0?</h3>
            <div class="faq-content">
              <p>В гарантийном случае — изгоним злых духов повторно и бесплатно. В негарантийном — тоже несите, мы любим вызовы и пополнять коллекцию техно-ужасов.</p>
            </div>
            <i class="bi bi-chevron-down faq-toggle"></i>
          </div><!-- End Faq item-->
        </div>
      </div>
    </div>
  </div>

</section><!-- /Faq Section -->

<!-- Team Section -->
<section id="team" class="team section">

  <div class="container" data-aos="fade-up">
    <!-- Section Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="section-title" style="padding-bottom: 0; margin-bottom: 0;">
        <span class="description-title">Лица, ответственные за магию</span>
        <h2>Наши техно-шаманы</h2>
      </div>
      <div class="swiper-nav-buttons">
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
    </div>

    <?php
    $team_members = [
        [
            'name' => 'Виртуоз Паяльника',
            'role' => 'Мастер-джедай',
            'description' => 'Может воскресить даже самый безнадежный тостер. Говорят, его паяльник нашептывает ему секреты электроники.',
        ],
        [
            'name' => 'Повелительница Дедлайнов',
            'role' => 'Главный администратор',
            'description' => 'Одной левой принимает заявки, правой — успокаивает взбунтовавшиеся принтеры. Ее спокойствию завидуют серверные стойки.',
        ],
        [
            'name' => 'Профессор Процессоров',
            'role' => 'Специалист по ноутбукам',
            'description' => 'Знает термопасту на вкус и может диагностировать перегрев по запаху. Считает, что синий экран — это просто грустный смайлик.',
        ],
        [
            'name' => 'Фея Чистых Кодов',
            'role' => 'Контент-менеджер',
            'description' => 'Наполняет сайт текстами, которые понятны даже вашему коту. Умеет превращать технические термины в увлекательные истории.',
        ],
        [
            'name' => 'Конденсаторный Колдун',
            'role' => 'Мастер по платам',
            'description' => 'Лечит вздувшиеся конденсаторы наложением рук и канифоли. Говорит, что у каждой микросхемы есть душа.',
        ],
         [
            'name' => 'Укротитель Вирусов',
            'role' => 'Эксперт по безопасности',
            'description' => 'Охотится на троянов в диких джунглях интернета. Его антивирус не знает пощады.',
        ],
    ];
    $team_images = glob('images/person/*.webp');
    ?>

    <div class="team-slider-wrapper" data-aos="fade-up" data-aos-delay="100">
      <div class="team-slider swiper init-swiper">
        <div class="swiper-config">
          {
          "loop": true,
          "speed": 600,
          "autoplay": {
          "delay": 5000
          },
          "slidesPerView": "auto",
          "pagination": {
          "el": ".swiper-pagination",
          "type": "bullets",
          "clickable": true
          },
          "navigation": {
          "nextEl": ".swiper-button-next",
          "prevEl": ".swiper-button-prev"
          },
          "breakpoints": {
          "320": {
          "slidesPerView": 1,
          "spaceBetween": 40
          },
          "768": {
          "slidesPerView": 2,
          "spaceBetween": 40
          },
          "1200": {
          "slidesPerView": 4,
          "spaceBetween": 20
          }
          }
          }
        </div>
        <div class="swiper-wrapper">
          <?php if (!empty($team_members)): ?>
            <?php foreach ($team_members as $index => $member): ?>
              <div class="swiper-slide">
                <div class="team-member">
                  <div class="member-image">
                    <img src="<?= !empty($team_images) ? $team_images[$index % count($team_images)] : 'images/person/person-m-1.webp' ?>" class="img-fluid" alt="">
                  </div>
                  <div class="member-content">
                    <h3><?= htmlspecialchars($member['name']) ?></h3>
                    <span><?= htmlspecialchars($member['role']) ?></span>
                    <p>
                      <?= htmlspecialchars($member['description']) ?>
                    </p>
                  </div>
                </div>
              </div><!-- End slide item -->
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="swiper-pagination"></div>
      </div>
    </div>
  </div>

</section><!-- /Team Section -->

<?php
require_once 'includes/footer.php';
?>