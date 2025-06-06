<?php
require_once 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
    <div class="container">
        <div class="section-title">
            <span class="description-title">Наша подпольная лаборатория</span>
            <h2>Где паяльник встречается с вдохновением</h2>
        </div>
    </div>
</section><!-- /Page Title -->

<!-- About Content Section -->
<section id="about-content" class="about-content section light-background">
    <div class="container">
        
        <div class="row align-items-center gy-5">
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="about-image-wrapper position-relative">
                    <img src="images/about/about-8.webp" alt="Наша мастерская" class="img-fluid rounded-4">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="about-content ps-lg-4">
                    <h3>Наша почти правдивая история</h3>
                    <p>Все началось в гараже, заваленном старыми тостерами и одним очень амбициозным хомяком-инженером. Мы мечтали создать место, где техника не просто ремонтируется, а проходит курс психотерапии и обретает второе (а иногда и третье) дыхание.</p>
                    <p>Сегодня наш сервисный центр — это результат многолетних экспериментов, тысяч выпитых чашек кофе и бесконечных споров о том, какой стороной вставлять USB. Мы гордимся тем, что до сих пор не взорвали ничего важного и продолжаем нашу благородную миссию по спасению мира от техно-хлама.</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Why Choose Us Section -->
<section id="why-us" class="why-us section">
    <div class="container" data-aos="fade-up">

        <div class="section-title text-center">
            <h2>Почему мы, а не сосед с отверткой?</h2>
            <p>Есть как минимум четыре причины доверить нам своего электронного друга.</p>
        </div>

        <div class="row g-4">

            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="why-us-item">
                    <div class="icon">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <h3>Честная гарантия</h3>
                    <p>Наша гарантия действует до тех пор, пока вы не моргнете. Шутка. Она честная, почти как наши налоги.</p>
                </div>
            </div><!-- End Why Us Item -->

            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="why-us-item">
                    <div class="icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>Опытные шаманы</h3>
                    <p>Наши мастера видели больше синих экранов смерти, чем вы — смешных видео с котиками. Им можно доверять.</p>
                </div>
            </div><!-- End Why Us Item -->

            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="why-us-item">
                    <div class="icon">
                        <i class="bi bi-stopwatch"></i>
                    </div>
                    <h3>Скорость света (почти)</h3>
                    <p>Мы работаем так быстро, что ваша кофеварка не успеет остыть. Диагностика быстрее, чем доставка пиццы.</p>
                </div>
            </div><!-- End Why Us Item -->

            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="why-us-item">
                    <div class="icon">
                        <i class="bi bi-gem"></i>
                    </div>
                    <h3>Редкие артефакты</h3>
                    <p>Используем только проверенные детали. Некоторые из них мы добыли в археологических экспедициях.</p>
                </div>
            </div><!-- End Why Us Item -->

        </div>
    </div>
</section><!-- /Why Choose Us Section -->

<!-- Team Section -->
<section id="team" class="team section light-background">

  <div class="container" data-aos="fade-up">
    <!-- Section Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="section-title" style="padding-bottom: 0; margin-bottom: 0;">
        <span class="description-title">Наша команда</span>
        <h2>Познакомьтесь с нашими специалистами</h2>
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