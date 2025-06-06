<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Сервисный центр</title>

  <!-- Favicons -->
  <link href="/images/favicon.png" rel="icon">
  <link href="/images/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="/assets/css/main.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">

</head>

<body class="<?= basename($_SERVER['SCRIPT_NAME'], '.php') ?>-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid position-relative d-flex align-items-center justify-content-between">

      <a href="/" class="logo d-flex align-items-center">
        <h1 class="sitename">Сервисный центр</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
            <li><a href="/" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">Главная</a></li>
            <li><a href="/services.php" class="<?= ($current_page == 'services.php') ? 'active' : '' ?>">Услуги</a></li>
            <li><a href="/contacts.php" class="<?= ($current_page == 'contacts.php') ? 'active' : '' ?>">Контакты</a></li>
            <li><a href="/about.php" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>">О нас</a></li>
            <li><a href="/reviews.php" class="<?= ($current_page == 'reviews.php') ? 'active' : '' ?>">Отзывы</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="/profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : '' ?>">Профиль</a></li>
                <li><a href="#" data-action="logout">Выйти</a></li>
            <?php else: ?>
                <li><a href="/login.php" class="<?= ($current_page == 'login.php') ? 'active' : '' ?>">Вход</a></li>
                <li><a href="/register.php" class="<?= ($current_page == 'register.php') ? 'active' : '' ?>">Регистрация</a></li>
            <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>
  <main class="main">
    <?php
        $notification_message = '';
        $notification_type = '';
        if (isset($_SESSION['notification'])) {
            $notification_message = htmlspecialchars($_SESSION['notification']['message']);
            $notification_type = $_SESSION['notification']['type'] === 'success' ? 'success' : 'error';
            unset($_SESSION['notification']);
        }
    ?>
    <div class="container-fluid" style="position: relative; z-index: 1050;">
        <div
            id="notification"
            class="alert"
            style="display:none; position: fixed; top: 90px; left: 50%; transform: translateX(-50%); max-width: 80%;"
            data-message="<?= $notification_message ?>"
            data-type="<?= $notification_type ?>"
        ></div>
    </div>
  </main>

</body>
</html> 