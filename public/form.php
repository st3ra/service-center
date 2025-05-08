<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/handlers/form_handler.php';

// Проверяем, является ли запрос AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_GET['service_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Не указан ID услуги']]);
        exit;
    }
    die('Не указан ID услуги');
}

$service_id = (int)$_GET['service_id'];
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Услуга не найдена']]);
        exit;
    }
    die('Услуга не найдена');
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Обрабатываем POST-запрос (для AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_form_submission($pdo, $service_id, $user);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    header('Location: /request.php?id=' . $result['request_id']);
    exit;
}

// Рендерим HTML только для не-AJAX GET-запросов
require_once 'includes/header.php';
?>

<h1>Запись на услугу: <?php echo htmlspecialchars($service['name']); ?></h1>

<div id="notification" class="alert" style="display:none;"></div>

<form id="request-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
    <?php if ($user): ?>
        <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <?php else: ?>
        <div class="mb-3">
            <label for="name" class="form-label">ФИО</label>
            <input type="text" class="form-control" id="name" name="name" required>
            <div class="invalid-feedback"></div>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
            <div class="invalid-feedback"></div>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback"></div>
        </div>
    <?php endif; ?>
    <div class="mb-3">
        <label for="description" class="form-label">Описание проблемы</label>
        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="file" class="form-label">Прикрепить файлы (jpg, png, pdf, до 5 МБ каждый)</label>
        <input type="file" class="form-control" id="file" name="files[]" multiple accept=".jpg,.png,.pdf">
        <div class="invalid-feedback"></div>
        <div id="image-preview" class="mt-2"></div>
    </div>
    <button type="submit" class="btn btn-primary">Отправить заявку</button>
</form>

<?php
require_once 'includes/footer.php';
?>