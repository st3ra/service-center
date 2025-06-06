<?php
require_once 'includes/handlers/login_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    handle_login($pdo);
    exit;
}

require_once 'includes/header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container section-title">
      <span class="description-title">Авторизация</span>
      <h2>Вход в аккаунт</h2>
      <p>Войдите, чтобы получить доступ к вашим заявкам и профилю.</p>
    </div>
  </div><!-- End Page Title -->

  <!-- Login Form Section -->
  <section id="login-section" class="form-section section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5">
          <div class="request-main-card auth-form-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-body">
              <div id="notification" style="display:none; margin-bottom: 20px;"></div>

              <form id="login-form" class="php-request-form">
                <div class="row gy-4">
                  <div class="col-12">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="col-12">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="col-12 text-center">
                    <button type="submit" class="btn primary-btn w-100">Войти</button>
                  </div>

                  <div class="col-12 text-center">
                    <p class="small mt-3">Нет аккаунта? <a href="/register.php">Зарегистрироваться</a></p>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php
require_once 'includes/footer.php';
?>