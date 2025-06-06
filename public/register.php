<?php
require_once 'includes/handlers/register_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    handle_registration($pdo);
    exit;
}

require_once 'includes/header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container section-title">
      <span class="description-title">Регистрация</span>
      <h2>Создание нового аккаунта</h2>
      <p>Зарегистрируйтесь, чтобы получить возможность создавать и отслеживать ваши заявки на ремонт.</p>
    </div>
  </div><!-- End Page Title -->

  <!-- Register Form Section -->
  <section id="register-section" class="form-section section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="request-main-card auth-form-card" data-aos="fade-up" data-aos-delay="100">
             <div class="card-body">
                <div id="notification" style="display:none; margin-bottom: 20px;"></div>

                <form id="register-form" class="php-request-form">
                    <div class="row gy-4">
                        <div class="col-12">
                            <label for="name" class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                             <div class="invalid-feedback"></div>
                        </div>
                         <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                             <div class="invalid-feedback"></div>
                        </div>
                         <div class="col-md-6">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                             <div class="invalid-feedback"></div>
                        </div>
                         <div class="col-md-6">
                            <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                             <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="btn primary-btn w-100">Зарегистрироваться</button>
                        </div>
                        
                        <div class="col-12 text-center">
                           <p class="small mt-3">Уже есть аккаунт? <a href="/login.php">Войти</a></p>
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