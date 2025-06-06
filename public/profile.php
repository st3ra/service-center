<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/handlers/profile_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Необходимо войти']]);
        exit;
    }
    header('Location: /');
    exit;
}

if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_profile_edit($pdo);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

$stmt = $pdo->prepare('SELECT name, phone, email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден');
}

$stmt = $pdo->prepare('
    SELECT r.id, r.status, r.created_at, s.name AS service_name
    FROM requests r
    JOIN services s ON r.service_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';

function get_status_translation(string $status): string {
    $translations = [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'completed' => 'Выполнена',
        'cancelled' => 'Отменена',
        'pending' => 'В ожидании'
    ];
    return $translations[$status] ?? ucfirst($status);
}
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container">
       <div class="section-title">
          <span class="description-title">Личный кабинет</span>
          <h2>Профиль пользователя</h2>
       </div>
    </div>
  </div><!-- End Page Title -->

  <!-- Profile Section -->
  <section id="profile-section" class="profile-section section">
    <div class="container">
        <div class="row gy-4 justify-content-center">
            <div class="col-lg-10" data-aos="fade-up" data-aos-delay="100">
                <div class="request-main-card">
                    <!-- Profile Info Section -->
                    <div id="profile-view" class="p-4 border-bottom position-relative">
                         <div class="mb-3">
                             <h4 class="m-0"><i class="bi bi-person-circle me-2"></i>Личные данные</h4>
                         </div>
                        <ul class="info-list">
                            <li><strong>ФИО:</strong> <span id="view-name"><?= htmlspecialchars($user['name']) ?></span></li>
                            <li><strong>Телефон:</strong> <span id="view-phone"><?= htmlspecialchars($user['phone']) ?></span></li>
                            <li><strong>Email:</strong> <span id="view-email"><?= htmlspecialchars($user['email']) ?></span></li>
                        </ul>
                        <button id="edit-profile-btn" class="primary-btn profile-edit-btn">Редактировать</button>
                    </div>
                    <!-- Profile Edit Form (Initially Hidden) -->
                    <form id="profile-edit-form" class="php-request-form p-4 border-bottom" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="m-0"><i class="bi bi-pencil-square me-2"></i>Редактирование</h4>
                        </div>
                        <div id="edit-notification" class="alert" style="display:none; margin-bottom: 20px;"></div>
                        <div class="row gy-3">
                             <div class="col-12">
                                <label for="name" class="form-label">ФИО</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <button type="button" id="cancel-edit-btn" class="btn danger-btn">Отмена</button>
                                <button type="submit" class="btn primary-btn">Сохранить</button>
                            </div>
                        </div>
                    </form>

                    <!-- Requests List Section -->
                    <div class="p-4">
                        <div class="section-title mb-4">
                           <span class="description-title">История</span>
                           <h2>Мои заявки</h2>
                        </div>
                        <?php if (empty($requests)): ?>
                            <div class="text-center p-4 border-top">
                              <p>У вас еще нет заявок.</p>
                              <a href="/services.php" class="primary-btn">Посмотреть услуги</a>
                            </div>
                        <?php else: ?>
                            <div class="requests-list">
                                <?php foreach ($requests as $request): ?>
                                    <div class="request-item">
                                        <div class="request-item-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <a href="request.php?id=<?= $request['id'] ?>" class="request-id me-3">Заявка #<?= $request['id'] ?></a>
                                                <span class="status-badge status-<?= htmlspecialchars(strtolower($request['status'])) ?>">
                                                  <?= get_status_translation($request['status']) ?>
                                                </span>
                                            </div>
                                            <p class="request-service"><?= htmlspecialchars($request['service_name']) ?></p>
                                            <p class="request-date">Создана: <?= date('d.m.Y', strtotime($request['created_at'])) ?></p>
                                        </div>
                                        <div class="request-item-status">
                                            <a href="request.php?id=<?= $request['id'] ?>" class="primary-btn">Подробнее</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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