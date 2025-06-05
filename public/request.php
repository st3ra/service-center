<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/handlers/request_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Определяем, авторизован ли пользователь или это гость с правом просмотра
$is_guest_view = false;
if (!isset($_SESSION['user_id'])) {
    $allow_guest = false;
    if (isset($_SESSION['guest_request_id']) && isset($_GET['id'])) {
        $guest_id = (int)$_SESSION['guest_request_id'];
        $req_id = (int)$_GET['id'];
        if ($guest_id === $req_id) {
            $allow_guest = true;
            $is_guest_view = true;
        }
    }
    if (!$allow_guest) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['errors' => ['general' => 'Необходимо войти']]);
            exit;
        }
        header('Location: /');
        exit;
    }
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
    if (isset($_POST['action']) && $_POST['action'] === 'edit_request') {
        $description = trim($_POST['description'] ?? '');
        $files_to_delete = json_decode($_POST['files_to_delete'] ?? '[]', true);
        $file_paths = [];
        $errors = [];

        $errors = array_merge($errors, validate_form(['description' => $description]));

        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (!is_writable($upload_dir)) {
                $errors['files'] = 'Нет прав на запись в папку uploads/';
            } else {
                $allowed_ext = ['jpg', 'png', 'pdf'];
                $file_count = count($_FILES['files']['name']);
                for ($i = 0; $i < $file_count; $i++) {
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
                    } elseif ($_FILES['files']['error'][$i] === UPLOAD_ERR_INI_SIZE) {
                        $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                        $errors['files'][] = "Файл '$file_name': Слишком большой для серверных настроек (макс. 5 МБ)";
                    } elseif ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                        $errors['files'][] = "Файл '$file_name': Ошибка загрузки, код {$_FILES['files']['error'][$i]}";
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('UPDATE requests SET description = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$description, $request_id, $_SESSION['user_id']]);

                foreach ($file_paths as $file_path) {
                    $stmt = $pdo->prepare('INSERT INTO request_files (request_id, file_path) VALUES (?, ?)');
                    $stmt->execute([$request_id, $file_path]);
                }

                if (!empty($files_to_delete)) {
                    $placeholders = implode(',', array_fill(0, count($files_to_delete), '?'));
                    $stmt = $pdo->prepare("SELECT id, file_path FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                    $stmt->execute(array_merge($files_to_delete, [$request_id]));
                    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($files as $file) {
                        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                    $stmt->execute(array_merge($files_to_delete, [$request_id]));
                }

                $pdo->commit();

                $stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
                $stmt->execute([$request_id]);
                $updated_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = [
                    'success' => 'Заявка обновлена',
                    'description' => $description,
                    'files' => $updated_files
                ];
                error_log('Edit request response: ' . json_encode($response));
                echo json_encode($response);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                error_log('Edit request error: ' . $e->getMessage());
                echo json_encode(['errors' => $errors]);
            }
        } else {
            error_log('Edit request validation errors: ' . json_encode($errors));
            echo json_encode(['errors' => $errors]);
        }
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_request') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT file_path FROM request_files WHERE request_id = ?');
            $stmt->execute([$request_id]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($files as $file) {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $stmt = $pdo->prepare('DELETE FROM request_files WHERE request_id = ?');
            $stmt->execute([$request_id]);

            $stmt = $pdo->prepare('DELETE FROM requests WHERE id = ? AND user_id = ?');
            $stmt->execute([$request_id, $_SESSION['user_id']]);

            $pdo->commit();

            $response = ['success' => 'Заявка удалена', 'redirect' => '/profile.php'];
            error_log('Delete request response: ' . json_encode($response));
            echo json_encode($response);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            error_log('Delete request error: ' . $e->getMessage());
            echo json_encode(['errors' => $errors]);
        }
        exit;
    }
}

if ($is_guest_view) {
    $stmt = $pdo->prepare('
        SELECT r.id, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
        FROM requests r
        JOIN services s ON r.service_id = s.id
        WHERE r.id = ?
    ');
    $stmt->execute([$request_id]);
} else {
    $stmt = $pdo->prepare('
        SELECT r.id, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
        FROM requests r
        JOIN services s ON r.service_id = s.id
        WHERE r.id = ? AND r.user_id = ?
    ');
    $stmt->execute([$request_id, $_SESSION['user_id']]);
}
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Заявка не найдена или доступ запрещён']]);
        exit;
    }
    die('Заявка не найдена или доступ запрещён');
}

$stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
$stmt->execute([$request_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Обработка отзыва ---
$review_error = '';
$review_success = '';
$review_text = '';
$can_leave_review = false;
$review = null;

if (isset($request['id'])) {
    // Проверяем, есть ли отзыв по этой заявке
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE request_id = ?');
    $stmt->execute([$request['id']]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    // Можно оставить отзыв, если заявка выполнена, отзыв не оставлен, пользователь авторизован и заявка его
    if (
        isset($_SESSION['user_id']) &&
        !$is_guest_view &&
        $request['status'] === 'completed' &&
        !$review
    ) {
        $can_leave_review = true;
    }

    // Обработка отправки отзыва
    if ($can_leave_review && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
        $review_text = trim($_POST['review_text']);
        if (mb_strlen($review_text) < 5) {
            $review_error = 'Отзыв слишком короткий.';
        } elseif (mb_strlen($review_text) > 1000) {
            $review_error = 'Отзыв слишком длинный (максимум 1000 символов).';
        } else {
            // Получаем имя пользователя по user_id
            $user_name = '';
            $stmt_user = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt_user->execute([$_SESSION['user_id']]);
            if ($row = $stmt_user->fetch()) {
                $user_name = $row['name'];
            }
            $stmt = $pdo->prepare('INSERT INTO reviews (request_id, user_id, author, text) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $request['id'],
                $_SESSION['user_id'],
                $user_name,
                $review_text
            ]);
            $review_success = 'Спасибо за ваш отзыв!';
            // Получаем только что добавленный отзыв
            $stmt = $pdo->prepare('SELECT * FROM reviews WHERE request_id = ?');
            $stmt->execute([$request['id']]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            $can_leave_review = false;
        }
    }
}

require_once 'includes/header.php';
?>

<h1>Заявка #<?php echo $request['id']; ?></h1>

<div id="notification" class="alert" style="display:none;"></div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Информация о заявке</h5>
        <div id="request-view">
            <p><strong>Услуга:</strong> <?php echo htmlspecialchars($request['service_name']); ?></p>
            <p><strong>Статус:</strong> <?php echo htmlspecialchars($request['status']); ?></p>
            <p><strong>Дата создания:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
            <div id="description-container">
                <p><strong>Описание:</strong> <span id="description-text"><?php echo htmlspecialchars($request['description'] ?: 'Отсутствует'); ?></span></p>
            </div>
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
            <button class="btn btn-outline-primary btn-sm" id="edit-request-btn"><i class="bi bi-pencil"></i> Редактировать</button>
            <button class="btn btn-outline-danger btn-sm ms-2" id="delete-request-btn"><i class="bi bi-trash"></i> Удалить заявку</button>
        </div>
    </div>
</div>

<?php
if ($review) {
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h5 class="card-title">Ваш отзыв</h5>';
    echo '<p class="mb-1"><b>Текст:</b> ' . htmlspecialchars($review['text']) . '</p>';
    echo '<p class="mb-0 text-muted"><small>Оставлен: ' . htmlspecialchars($review['created_at']) . '</small></p>';
    echo '</div></div>';
} elseif ($can_leave_review) {
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h5 class="card-title">Оставить отзыв</h5>';
    if ($review_error) echo '<div class="alert alert-danger">' . htmlspecialchars($review_error) . '</div>';
    if ($review_success) echo '<div class="alert alert-success">' . htmlspecialchars($review_success) . '</div>';
    echo '<form method="post"><div class="mb-3">';
    echo '<textarea name="review_text" class="form-control" rows="4" maxlength="1000" required placeholder="Ваш отзыв...">' . htmlspecialchars($review_text) . '</textarea>';
    echo '</div><button type="submit" class="btn btn-success">Отправить отзыв</button></form>';
    echo '</div></div>';
}
?>
<a href="profile.php" class="btn btn-outline-secondary">Назад к профилю</a>
<?php
require_once 'includes/footer.php';
?>