<?php
require_once 'includes/header.php';
require_once 'includes/config.php'; // Подключаем загрузчик .env

// Получаем ключ из переменных окружения с запасным вариантом
$yandex_api_key = $_ENV['YANDEX_MAPS_API_KEY'] ?? '';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container section-title">
      <span class="description-title">Контакты</span>
      <h2>Как с нами связаться</h2>
      <p>Мы всегда готовы ответить на ваши вопросы и помочь с решением любых проблем. Вы можете связаться с нами любым удобным для вас способом.</p>
    </div>
  </div><!-- End Page Title -->

  <!-- Contact Section -->
  <section id="contact" class="contact section">

    <div class="container" data-aos="fade-up" data-aos-delay="100">

      <div class="row gy-4">

        <div class="col-lg-12">
          <div class="row gy-4">
            <div class="col-md-4">
              <div class="info-item" data-aos="fade" data-aos-delay="200">
                <i class="bi bi-geo-alt"></i>
                <h3>Адрес</h3>
                <p>г. Москва, ул. Примерная, д. 1, офис 101</p>
              </div>
            </div><!-- End Info Item -->

            <div class="col-md-4">
              <div class="info-item" data-aos="fade" data-aos-delay="300">
                <i class="bi bi-telephone"></i>
                <h3>Телефон</h3>
                <p>+7 (495) 123-45-67</p>
              </div>
            </div><!-- End Info Item -->

            <div class="col-md-4">
              <div class="info-item" data-aos="fade" data-aos-delay="400">
                <i class="bi bi-envelope"></i>
                <h3>Email</h3>
                <p>info@service-center.ru</p>
              </div>
            </div><!-- End Info Item -->

          </div>
        </div>
      </div>
    </div>
  </section><!-- /Contact Section -->

  <!-- Map Section -->
  <section id="map" class="map section">
    <div class="container section-title" data-aos="fade-up">
        <span class="description-title">Мы на карте</span>
        <h2>Найдите нас</h2>
    </div>
     <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div id="yandex-map" style="width: 100%; height: 550px;"></div>
     </div>
  </section>

</main>

<script src="https://api-maps.yandex.ru/v3/?apikey=<?= htmlspecialchars($yandex_api_key) ?>&lang=ru_RU"></script>

<script type="text/javascript">
    async function init() {
        // Если ключ не загрузился, не пытаемся инициализировать карту
        if (!'<?= htmlspecialchars($yandex_api_key) ?>') {
            document.getElementById('yandex-map').innerHTML = '<div class="alert alert-warning">Не удалось загрузить карту. Ключ API не найден.</div>';
            console.error('Yandex Maps API key is not configured.');
            return;
        }

        try {
            await ymaps3.ready;
            const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapControls} = ymaps3;
            const {YMapZoomControl} = await ymaps3.import('@yandex/ymaps3-controls@0.0.1');
            const {YMapDefaultMarker} = await ymaps3.import('@yandex/ymaps3-markers@0.0.1');

            const map = new YMap(
                document.getElementById('yandex-map'),
                {
                    location: {
                        center: [37.623082, 55.752540], // Координаты сервисного центра
                        zoom: 16
                    }
                }
            );

            // Cлои и контролы
            map.addChild(new YMapDefaultSchemeLayer({theme: 'dark'}));
            map.addChild(new YMapDefaultFeaturesLayer({}));
            map.addChild(new YMapControls({position: 'right'}).addChild(new YMapZoomControl({})));

            // Единственный маркер организации
            const mainMarker = new YMapDefaultMarker({
                coordinates: [37.623082, 55.752540],
                title: 'Наш сервисный центр',
                subtitle: 'Заходите в гости!',
                color: '#1a73e8'
            });
            map.addChild(mainMarker);

        } catch (e) {
            console.error('Ошибка при инициализации карты:', e);
            document.getElementById('yandex-map').innerHTML = '<div class="alert alert-danger">Ошибка при загрузке карты.</div>';
        }
    }
    init();
</script>

<?php
require_once 'includes/footer.php';
?>