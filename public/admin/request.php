<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth_check.php';  // Проверка доступа (admin, worker, editor)
require_once '../includes/db.php';
require_once '../includes/validation.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Проверка ID заявки
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'ID заявки не указан']]);
        exit;
    }
    die('ID заявки не указан');
}

$request_id = (int)$_GET['id'];
$is_admin = $_SESSION['role'] === 'admin';
$is_worker = $_SESSION['role'] === 'worker';
$is_editor = $_SESSION['role'] === 'editor';

// Загрузка заявки
$stmt = $pdo->prepare('
    SELECT r.id, r.user_id, r.name, r.phone, r.email, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
    FROM requests r
    JOIN services s ON r.service_id = s.id
    WHERE r.id = ?
');
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Заявка не найдена']]);
        exit;
    }
    die('Заявка не найдена');
}

// Загрузка файлов
$stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
$stmt->execute([$request_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Загрузка комментариев
$stmt = $pdo->prepare('
    SELECT c.id, c.comment, c.created_at, c.user_id, u.name AS author
    FROM request_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.request_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$request_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Загрузка услуг для выпадающего списка
$stmt = $pdo->query('SELECT id, name FROM services ORDER BY name');
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка POST-запросов
if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $response = [];

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit_request' && ($is_admin || $is_worker)) {
            $errors = [];
            $response = [];
            
            // Данные формы для админа
            if ($is_admin) {
                $form_data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'service_id' => (int)($_POST['service_id'] ?? 0),
                    'description' => trim($_POST['description'] ?? '')
                ];
                $files_to_delete = json_decode($_POST['files_to_delete'] ?? '[]', true);
                $file_paths = [];

                // Валидация полей админа
                $errors = array_merge($errors, validate_form($form_data));
                if (!isset($errors['email']) && $form_data['email']) {
                    $email_error = validate_email($form_data['email']);
                    if ($email_error) $errors['email'] = $email_error;
                }
                if ($form_data['service_id'] <= 0) {
                    $errors['service_id'] = 'Выберите услугу';
                }

                // Обработка файлов
                if (!empty($_FILES['files']['name'][0])) {
                    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    if (!is_writable($upload_dir)) {
                        $errors['files'] = 'Нет прав на запись в папку uploads/';
                    } else {
                        $allowed_ext = ['jpg', 'png', 'pdf'];
                        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                                $file_tmp = $_FILES['files']['tmp_name'][$i];
                                $file_name = $_FILES['files']['name'][$i];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $file_size = $_FILES['files']['size'][$i];
                                if (!in_array($file_ext, $allowed_ext)) {
                                    $errors['files'][] = "Файл '$file_name': Неверный формат";
                                } elseif ($file_size > 5 * 1024 * 1024) {
                                    $errors['files'][] = "Файл '$file_name': Слишком большой";
                                } else {
                                    $upload_path = $upload_dir . uniqid() . '.' . $file_ext;
                                    if (move_uploaded_file($file_tmp, $upload_path)) {
                                        $file_paths[] = 'uploads/' . basename($upload_path);
                                    } else {
                                        $errors['files'][] = "Файл '$file_name': Ошибка загрузки";
                                    }
                                }
                            } elseif ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                                $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                                $errors['files'][] = "Файл '$file_name': Ошибка загрузки";
                            }
                        }
                    }
                }
            }

            // Проверка статуса (для всех ролей)
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['new', 'in_progress', 'completed'])) {
                $errors['status'] = 'Недопустимый статус';
            }

            // Проверка комментария (для всех ролей)
            $comment = trim($_POST['comment'] ?? '');
            if ($comment && strlen($comment) > 1000) {
                $errors['comment'] = 'Комментарий слишком длинный (максимум 1000 символов)';
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    if ($is_admin) {
                        // Обновление заявки (только для админа)
                        $stmt = $pdo->prepare('
                            UPDATE requests 
                            SET name = ?, phone = ?, email = ?, service_id = ?, description = ?, status = ?
                            WHERE id = ?
                        ');
                        $stmt->execute([
                            $form_data['name'],
                            $form_data['phone'],
                            $form_data['email'],
                            $form_data['service_id'],
                            $form_data['description'],
                            $status,
                            $request_id
                        ]);

                        // Добавление новых файлов
                        foreach ($file_paths as $file_path) {
                            $stmt = $pdo->prepare('INSERT INTO request_files (request_id, file_path) VALUES (?, ?)');
                            $stmt->execute([$request_id, $file_path]);
                        }

                        // Удаление файлов
                        if (!empty($files_to_delete)) {
                            $placeholders = implode(',', array_fill(0, count($files_to_delete), '?'));
                            $stmt = $pdo->prepare("SELECT id, file_path FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                            $stmt->execute(array_merge($files_to_delete, [$request_id]));
                            $files_to_remove = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($files_to_remove as $file) {
                                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                                if (file_exists($file_path)) unlink($file_path);
                            }

                            $stmt = $pdo->prepare("DELETE FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                            $stmt->execute(array_merge($files_to_delete, [$request_id]));
                        }
                    } else {
                        // Обновление только статуса (для работника)
                        $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE id = ?');
                        $stmt->execute([$status, $request_id]);
                    }

                    // Добавление комментария (для всех ролей)
                    $new_comment = null;
                    if ($comment) {
                        $stmt = $pdo->prepare('
                            INSERT INTO request_comments (request_id, user_id, comment)
                            VALUES (?, ?, ?)
                        ');
                        $stmt->execute([$request_id, $_SESSION['user_id'], $comment]);

                        $comment_id = $pdo->lastInsertId();
                        $stmt = $pdo->prepare('
                            SELECT c.id, c.comment, c.created_at, c.user_id, u.name AS author
                            FROM request_comments c
                            JOIN users u ON c.user_id = u.id
                            WHERE c.id = ?
                        ');
                        $stmt->execute([$comment_id]);
                        $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);
                    }

                    $pdo->commit();

                    // Формируем ответ
                    $response['success'] = 'Заявка обновлена';
                    
                    if ($is_admin) {
                        // Получение обновленных данных для ответа (для админа)
                        $stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
                        $stmt->execute([$request_id]);
                        $updated_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $stmt = $pdo->prepare('SELECT name AS service_name FROM services WHERE id = ?');
                        $stmt->execute([$form_data['service_id']]);
                        $service = $stmt->fetch(PDO::FETCH_ASSOC);

                        $response['request_data'] = array_merge(
                            $form_data,
                            [
                                'files' => $updated_files,
                                'service_name' => $service['service_name'],
                                'status' => $status,
                                'created_at' => $request['created_at']
                            ]
                        );
                    } else {
                        // Для работника возвращаем только статус
                        $response['request_data'] = [
                            'status' => $status
                        ];
                    }

                    // Добавляем комментарий в ответ, если он был создан
                    if ($new_comment) {
                        $response['comment'] = $new_comment;
                    }
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                }
            }

            header('Content-Type: application/json');
            echo json_encode($errors ? ['errors' => $errors] : $response);
            exit;
        } elseif ($_POST['action'] === 'update_status' && ($is_admin || $is_worker)) {
            // Изменение статуса (для admin и worker)
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['new', 'in_progress', 'completed'])) {
                $errors['status'] = 'Недопустимый статус';
            } else {
                try {
                    $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE id = ?');
                    $stmt->execute([$status, $request_id]);
                    $response = ['success' => 'Статус обновлен', 'status' => $status];
                } catch (PDOException $e) {
                    $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'add_comment' && ($is_admin || $is_worker)) {
            // Добавление комментария (для admin и worker)
            $comment = trim($_POST['comment'] ?? '');
            if (empty($comment)) {
                $errors['comment'] = 'Комментарий не может быть пустым';
            } else {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO request_comments (request_id, user_id, comment)
                        VALUES (?, ?, ?)
                    ');
                    $stmt->execute([$request_id, $_SESSION['user_id'], $comment]);

                    $comment_id = $pdo->lastInsertId();
                    $stmt = $pdo->prepare('
                        SELECT c.id, c.comment, c.created_at, c.user_id, u.name AS author
                        FROM request_comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.id = ?
                    ');
                    $stmt->execute([$comment_id]);
                    $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

                    $response = ['success' => 'Комментарий добавлен', 'comment' => $new_comment];
                } catch (PDOException $e) {
                    $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit_comment' && ($is_admin || $is_worker)) {
            // Редактирование комментария (для admin и worker)
            $comment_id = (int)($_POST['comment_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            
            if (empty($comment)) {
                $errors['comment'] = 'Комментарий не может быть пустым';
            } else {
                try {
                    // Проверяем, что комментарий принадлежит работнику
                    if ($is_worker) {
                        $stmt = $pdo->prepare('
                            SELECT id FROM request_comments 
                            WHERE id = ? AND request_id = ? AND user_id = ?
                        ');
                        $stmt->execute([$comment_id, $request_id, $_SESSION['user_id']]);
                        if (!$stmt->fetch()) {
                            $errors['general'] = 'У вас нет прав для редактирования этого комментария';
                        } else {
                            // Обновляем комментарий только если у пользователя есть права
                            $stmt = $pdo->prepare('
                                UPDATE request_comments 
                                SET comment = ?
                                WHERE id = ? AND request_id = ?
                            ');
                            $stmt->execute([$comment, $comment_id, $request_id]);

                            $stmt = $pdo->prepare('
                                SELECT c.id, c.comment, c.created_at, c.user_id, u.name AS author
                                FROM request_comments c
                                JOIN users u ON c.user_id = u.id
                                WHERE c.id = ?
                            ');
                            $stmt->execute([$comment_id]);
                            $updated_comment = $stmt->fetch(PDO::FETCH_ASSOC);

                            $response = ['success' => 'Комментарий обновлен', 'comment' => $updated_comment];
                        }
                    } else {
                        // Для админа обновляем без дополнительных проверок
                        $stmt = $pdo->prepare('
                            UPDATE request_comments 
                            SET comment = ?
                            WHERE id = ? AND request_id = ?
                        ');
                        $stmt->execute([$comment, $comment_id, $request_id]);

                        $stmt = $pdo->prepare('
                            SELECT c.id, c.comment, c.created_at, c.user_id, u.name AS author
                            FROM request_comments c
                            JOIN users u ON c.user_id = u.id
                            WHERE c.id = ?
                        ');
                        $stmt->execute([$comment_id]);
                        $updated_comment = $stmt->fetch(PDO::FETCH_ASSOC);

                        $response = ['success' => 'Комментарий обновлен', 'comment' => $updated_comment];
                    }
                } catch (PDOException $e) {
                    $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'delete_comment' && ($is_admin || $is_worker)) {
            // Удаление комментария (для admin и worker)
            $comment_id = (int)($_POST['comment_id'] ?? 0);
            try {
                // Проверяем, что комментарий принадлежит работнику
                if ($is_worker) {
                    $stmt = $pdo->prepare('
                        SELECT id FROM request_comments 
                        WHERE id = ? AND request_id = ? AND user_id = ?
                    ');
                    $stmt->execute([$comment_id, $request_id, $_SESSION['user_id']]);
                    if (!$stmt->fetch()) {
                        $errors['general'] = 'У вас нет прав для удаления этого комментария';
                    } else {
                        // Удаляем комментарий только если у пользователя есть права
                        $stmt = $pdo->prepare('DELETE FROM request_comments WHERE id = ? AND request_id = ?');
                        $stmt->execute([$comment_id, $request_id]);
                        $response = ['success' => 'Комментарий удален', 'comment_id' => $comment_id];
                    }
                } else {
                    // Для админа удаляем без дополнительных проверок
                    $stmt = $pdo->prepare('DELETE FROM request_comments WHERE id = ? AND request_id = ?');
                    $stmt->execute([$comment_id, $request_id]);
                    $response = ['success' => 'Комментарий удален', 'comment_id' => $comment_id];
                }
            } catch (PDOException $e) {
                $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'delete_request' && $is_admin) {
            // Удаление заявки (для admin)
            try {
                $pdo->beginTransaction();

                // Удаление файлов
                $stmt = $pdo->prepare('SELECT file_path FROM request_files WHERE request_id = ?');
                $stmt->execute([$request_id]);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($files as $file) {
                    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                    if (file_exists($file_path)) unlink($file_path);
                }
                $stmt = $pdo->prepare('DELETE FROM request_files WHERE request_id = ?');
                $stmt->execute([$request_id]);

                // Удаление комментариев
                $stmt = $pdo->prepare('DELETE FROM request_comments WHERE request_id = ?');
                $stmt->execute([$request_id]);

                // Удаление заявки
                $stmt = $pdo->prepare('DELETE FROM requests WHERE id = ?');
                $stmt->execute([$request_id]);

                $pdo->commit();
                $response = ['success' => 'Заявка удалена', 'redirect' => '/admin/requests.php'];
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            }
        } else {
            $errors['general'] = 'Недопустимое действие или недостаточно прав';
        }
    } else {
        $errors['general'] = 'Действие не указано';
    }

    header('Content-Type: application/json');
    echo json_encode($errors ? ['errors' => $errors] : $response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка #<?php echo $request['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Передаем ID заявки в JavaScript
        const requestId = <?php echo $request_id; ?>;
    </script>
    <script src="/assets/js/admin.request.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Заявка #<?php echo $request['id']; ?></h1>
            <a href="/admin/requests.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <div id="notification" class="alert" style="display:none;"></div>

        <!-- Информация о заявке -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Информация о заявке</h5>
                <div id="request-view">
                    <p><strong>Пользователь:</strong> <?php echo htmlspecialchars($request['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                    <p><strong>Услуга:</strong> <?php echo htmlspecialchars($request['service_name']); ?></p>
                    <p><strong>Статус:</strong> <span id="status-text"><?php echo htmlspecialchars($request['status']); ?></span></p>
                    <p><strong>Дата создания:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
                    <p><strong>Описание:</strong> <span id="description-text"><?php echo htmlspecialchars($request['description'] ?: 'Отсутствует'); ?></span></p>
                    <h6>Файлы:</h6>
                    <div id="file-list" class="mb-3">
                        <?php if (empty($files)): ?>
                            <p>Файлы отсутствуют</p>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <div class="file-item mb-2" data-file-id="<?php echo $file['id']; ?>">
                                    <?php if (preg_match('/\.(jpg|png)$/i', $file['file_path'])): ?>
                                        <img src="/<?php echo htmlspecialchars($file['file_path']); ?>" class="img-thumbnail me-2" style="max-width: 100px; max-height: 100px;">
                                    <?php else: ?>
                                        <a href="/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank"><?php echo htmlspecialchars(basename($file['file_path'])); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_admin || $is_worker): ?>
                        <button class="btn btn-outline-primary btn-sm" id="edit-request-btn"><i class="bi bi-pencil"></i> Редактировать</button>
                        <?php if ($is_admin): ?>
                            <button class="btn btn-outline-danger btn-sm ms-2" id="delete-request-btn"><i class="bi bi-trash"></i> Удалить заявку</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($is_admin || $is_worker): ?>
                    <form id="request-edit-form" style="display:none;" method="POST" enctype="multipart/form-data" data-user-id="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="action" value="edit_request">
                        <?php if ($is_admin): ?>
                        <div class="admin-only-fields">
                            <div class="mb-3">
                                <label for="name" class="form-label">Имя</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($request['name']); ?>" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($request['email']); ?>" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($request['phone']); ?>" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Услуга</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $request['service_id'] === $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Описание</label>
                                <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($request['description'] ?? ''); ?></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <?php if (!empty($files)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Текущие файлы</label>
                                    <div class="current-files">
                                        <?php foreach ($files as $file): ?>
                                            <div class="file-item mb-2" data-file-id="<?php echo $file['id']; ?>">
                                                <?php if (preg_match('/\.(jpg|png)$/i', $file['file_path'])): ?>
                                                    <img src="/<?php echo htmlspecialchars($file['file_path']); ?>" class="img-thumbnail me-2" style="max-width: 100px; max-height: 100px;">
                                                <?php else: ?>
                                                    <a href="/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank"><?php echo htmlspecialchars(basename($file['file_path'])); ?></a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger btn-sm delete-file-btn" data-file-id="<?php echo $file['id']; ?>">
                                                    <i class="bi bi-trash"></i> Удалить
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="files" class="form-label">Добавить файлы (jpg, png, pdf, до 5 МБ)</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple accept=".jpg,.png,.pdf">
                                <div class="invalid-feedback"></div>
                                <div id="files-preview" class="mt-2"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status">
                                <option value="new" <?php echo $request['status'] === 'new' ? 'selected' : ''; ?>>Новая</option>
                                <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label">Добавить комментарий</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="button" class="btn btn-secondary" id="cancel-edit-btn">Отмена</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Комментарии</h5>
                <div id="comment-list">
                    <?php if (empty($comments)): ?>
                        <p>Комментарии отсутствуют</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="card mb-2" data-comment-id="<?php echo $comment['id']; ?>">
                                <div class="card-body">
                                    <p class="card-text"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                    <p class="card-subtitle text-muted">
                                        <strong><?php echo htmlspecialchars($comment['author']); ?></strong>, 
                                        <?php echo htmlspecialchars($comment['created_at']); ?>
                                    </p>
                                    <?php if ($is_admin || ($is_worker && $comment['user_id'] == $_SESSION['user_id'])): ?>
                                        <button class="btn btn-outline-primary btn-sm edit-comment-btn">Редактировать</button>
                                        <button class="btn btn-outline-danger btn-sm delete-comment-btn">Удалить</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>