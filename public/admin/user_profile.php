<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth_check.php';  // Проверка, что пользователь — администратор
require_once '../includes/db.php';       // Подключение к базе данных
require_once '../includes/validation.php'; // Подключение валидации

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Проверка наличия ID пользователя
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Неверный ID пользователя']]);
        exit;
    }
    header('Location: /admin/users.php');
    exit;
}

$user_id = (int)$_GET['id'];
$errors = [];
$success = '';
$form_data = [];

// Загрузка данных пользователя
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.email, u.phone, u.role, COUNT(r.id) AS request_count
    FROM users u
    LEFT JOIN requests r ON u.id = r.user_id
    WHERE u.id = ?
    GROUP BY u.id
');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Пользователь не найден']]);
        exit;
    }
    header('Location: /admin/users.php');
    exit;
}

// В начале файла, после подключения файлов
$is_admin = $_SESSION['role'] === 'admin';

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка роли администратора
    if (!$is_admin) {
        $response = ['errors' => ['general' => 'У вас нет прав для этого действия']];
    } else {
        if (isset($_POST['edit_user'])) {
            // Редактирование пользователя
            $form_data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role' => $_POST['role'] ?? ''
            ];

            // Валидация
            $errors = array_merge($errors, validate_form([
                'name' => $form_data['name'],
                'email' => $form_data['email'],
                'phone' => $form_data['phone']
            ]));

            if (!isset($errors['email']) && $form_data['email']) {
                $email_error = validate_email($form_data['email']);
                if ($email_error) $errors['email'] = $email_error;
            }

            // Проверка уникальности email
            if (!isset($errors['email']) && $form_data['email'] !== $user['email']) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$form_data['email'], $user_id]);
                if ($stmt->fetch()) {
                    $errors['email'] = 'Этот email уже используется';
                }
            }

            // Проверка допустимой роли
            $valid_roles = ['admin', 'worker', 'editor', 'client'];
            if (!in_array($form_data['role'], $valid_roles)) {
                $errors['role'] = 'Недопустимая роль';
            }

            // Обновление данных
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare('
                        UPDATE users
                        SET name = ?, email = ?, phone = ?, role = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([
                        $form_data['name'],
                        $form_data['email'],
                        $form_data['phone'],
                        $form_data['role'],
                        $user_id
                    ]);
                    $response = [
                        'success' => 'Изменения сохранены',
                        'user_data' => array_merge(
                            $form_data,
                            [
                                'id' => $user_id,
                                'request_count' => $user['request_count']
                            ]
                        ),
                        'is_admin' => $is_admin
                    ];

                    // Обновляем данные пользователя для отображения
                    $user['name'] = $form_data['name'];
                    $user['email'] = $form_data['email'];
                    $user['phone'] = $form_data['phone'];
                    $user['role'] = $form_data['role'];
                } catch (PDOException $e) {
                    $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                    $response = ['errors' => $errors];
                }
            } else {
                $response = ['errors' => $errors, 'user_data' => $form_data];
            }
        } elseif (isset($_POST['delete_user'])) {
            // Удаление пользователя
            try {
                $pdo->beginTransaction();

                // Находим файлы, связанные с заявками пользователя
                $stmt = $pdo->prepare('
                    SELECT rf.file_path
                    FROM request_files rf
                    JOIN requests r ON rf.request_id = r.id
                    WHERE r.user_id = ?
                ');
                $stmt->execute([$user_id]);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Удаляем файлы с сервера
                foreach ($files as $file) {
                    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }

                // Удаляем записи из request_files
                $stmt = $pdo->prepare('
                    DELETE rf FROM request_files rf
                    JOIN requests r ON rf.request_id = r.id
                    WHERE r.user_id = ?
                ');
                $stmt->execute([$user_id]);

                // Удаляем заявки
                $stmt = $pdo->prepare('DELETE FROM requests WHERE user_id = ?');
                $stmt->execute([$user_id]);

                // Удаляем пользователя
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$user_id]);

                $pdo->commit();
                $response = ['success' => 'Пользователь удален', 'redirect' => '/admin/users.php'];
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                $response = ['errors' => $errors];
            }
        } else {
            $response = ['errors' => ['general' => 'Неверное действие']];
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        $errors = $response['errors'] ?? [];
        $success = $response['success'] ?? '';
        $form_data = $response['user_data'] ?? $form_data;
        if (isset($response['redirect'])) {
            header('Location: ' . $response['redirect']);
            exit;
        }
    }
}

// Загрузка списка заявок пользователя
$stmt = $pdo->prepare('
    SELECT r.id, r.status, r.created_at, s.name AS service_name
    FROM requests r
    JOIN services s ON r.service_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
');
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя #<?php echo $user['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin/utils.js"></script>
    <script src="/assets/js/admin/profile.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div id="notification" class="alert" style="display:none;"></div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Личные данные</h5>
                <div id="profile-view">
                    <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                    <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Роль:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    <p><strong>Количество заявок:</strong> <?php echo $user['request_count']; ?></p>
                    <?php if ($is_admin): ?>
                        <button class="btn btn-outline-primary btn-sm" id="edit-profile-btn"><i class="bi bi-pencil"></i> Редактировать</button>
                    <?php endif; ?>
                </div>
                <?php if ($is_admin): ?>
                <form id="profile-edit-form" style="display:none;" method="POST">
                    <input type="hidden" name="edit_user" value="1">
                    <div class="mb-3">
                        <label for="name" class="form-label">Имя</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? $user['name']); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? $user['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Телефон</label>
                        <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo htmlspecialchars($form_data['phone'] ?? $user['phone']); ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Роль</label>
                        <select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>" id="role" name="role" required>
                            <option value="admin" <?php echo ($form_data['role'] ?? $user['role']) === 'admin' ? 'selected' : ''; ?>>Админ</option>
                            <option value="worker" <?php echo ($form_data['role'] ?? $user['role']) === 'worker' ? 'selected' : ''; ?>>Работник сервиса</option>
                            <option value="editor" <?php echo ($form_data['role'] ?? $user['role']) === 'editor' ? 'selected' : ''; ?>>Редактор</option>
                            <option value="client" <?php echo ($form_data['role'] ?? $user['role']) === 'client' ? 'selected' : ''; ?>>Клиент</option>
                        </select>
                        <?php if (isset($errors['role'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['role']; ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-secondary" id="cancel-edit-btn">Отмена</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Удалить пользователя</h5>
                <form method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить пользователя? Это действие необратимо и удалит все связанные заявки и файлы.');">
                    <input type="hidden" name="delete_user" value="1">
                    <button type="submit" class="btn btn-danger">Удалить пользователя</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Заявки пользователя</h5>
                <?php if (empty($requests)): ?>
                    <p>Заявки отсутствуют</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Услуга</th>
                                <th>Статус</th>
                                <th>Дата создания</th>
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
                                    <td>
                                        <a href="/admin/request.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="bi bi-eye"></i> Просмотр
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <a href="/admin/users.php" class="btn btn-outline-secondary mt-3">Вернуться к списку пользователей</a>
    </div>
</body>
</html>