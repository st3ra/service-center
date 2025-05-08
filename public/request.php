<?php
// Запускаем сессию, если она ещё не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/request_handler.php';

// Проверяем, является ли запрос AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Необходимо войти']]);
        exit;
    }
    die('Необходимо войти');
}

if (!isset($_GET['id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'ID заявки не указан']]);
        exit;
    }
    die('ID заявки не указан');
}

$request_id = (int)$_GET['id'];

if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_request_edit($pdo, $request_id);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Проверяем, что заявка принадлежит пользователю
$stmt = $pdo->prepare('
    SELECT r.id, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
    FROM requests r
    JOIN services s ON r.service_id = s.id
    WHERE r.id = ? AND r.user_id = ?
');
$stmt->execute([$request_id, $_SESSION['user_id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Заявка не найдена или доступ запрещён']]);
        exit;
    }
    die('Заявка не найдена или доступ запрещён');
}

// Инициализируем данные для шаблона
$request_data = ['description' => $request['description'] ?? ''];
$errors = [];
$success = '';

// Обработка редактирования заявки для не-AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_request_edit($pdo, $request_id);
    $errors = $result['errors'] ?? [];
    $success = $result['success'] ?? '';
    $request_data = $result['request_data'] ?? $request_data;
}

// Включаем HTML только для не-AJAX запросов
require_once 'includes/header.php';
?>

<h1>Заявка #<?php echo $request['id']; ?></h1>

<div id="notification" class="alert" style="display:none;"></div>

<!-- Информация о заявке -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Информация о заявке</h5>
        <div id="request-view">
            <p><strong>Услуга:</strong> <?php echo htmlspecialchars($request['service_name']); ?></p>
            <p><strong>Статус:</strong> <?php echo htmlspecialchars($request['status']); ?></p>
            <p><strong>Дата создания:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
            <p><strong>Описание:</strong> <?php echo htmlspecialchars($request_data['description'] ?: 'Отсутствует'); ?></p>
            <button class="btn btn-outline-primary btn-sm" id="edit-request-btn"><i class="bi bi-pencil"></i> Редактировать</button>
        </div>
        <form id="request-edit-form" style="display:none;">
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="4"><?php echo htmlspecialchars($request_data['description']); ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <button type="button" class="btn btn-secondary" id="cancel-request-edit-btn">Отмена</button>
        </form>
    </div>
</div>

<a href="profile.php" class="btn btn-outline-secondary">Назад к профилю</a>

<?php
require_once 'includes/footer.php';
?>