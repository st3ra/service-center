<?php
// Запускаем сессию, если она ещё не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/handlers/profile_handler.php';

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

if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_profile_edit($pdo);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare('SELECT name, phone, email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден');
}

// Инициализируем данные для шаблона
$user_data = ['name' => $user['name'] ?? '', 'phone' => $user['phone'] ?? '', 'email' => $user['email'] ?? ''];
$errors = [];
$success = '';

// Обработка редактирования профиля для не-AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_profile_edit($pdo);
    $errors = $result['errors'] ?? [];
    $success = $result['success'] ?? '';
    $user_data = $result['user_data'] ?? $user_data;
}

// Получаем заявки пользователя с названием услуги
$stmt = $pdo->prepare('
    SELECT r.id, r.service_id, r.status, r.created_at, s.name AS service_name
    FROM requests r
    JOIN services s ON r.service_id = s.id
    WHERE r.user_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Включаем HTML только для не-AJAX запросов
require_once 'includes/header.php';
?>

<h1>Профиль</h1>

<div id="notification" class="alert" style="display:none;"></div>

<!-- Данные пользователя -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Личные данные</h5>
        <div id="profile-view">
            <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user_data['phone']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <button class="btn btn-outline-primary btn-sm" id="edit-profile-btn"><i class="bi bi-pencil"></i> Редактировать</button>
        </div>
        <form id="profile-edit-form" style="display:none;">
            <div class="mb-3">
                <label for="name" class="form-label">ФИО</label>
                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Телефон</label>
                <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <button type="button" class="btn btn-secondary" id="cancel-edit-btn">Отмена</button>
        </form>
    </div>
</div>

<!-- Список заявок -->
<h2>Мои заявки</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Услуга</th>
            <th>Статус</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo $request['id']; ?></td>
                <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                <td><?php echo htmlspecialchars($request['status']); ?></td>
                <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                <td><a href="request.php?id=<?php echo $request['id']; ?>" class="btn btn-outline-primary btn-sm">Подробнее</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
require_once 'includes/footer.php';
?>