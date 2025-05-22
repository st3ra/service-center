<?php
require_once 'includes/auth_check.php';
require_once '../includes/db.php';

// Получение списка категорий для выбора
$cat_stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Определение режима: создание новой услуги или редактирование существующей
$is_new = isset($_GET['new']) && $_GET['new'] == 1;
$service_id = 0;
$service = [
    'id' => 0,
    'name' => '',
    'category_id' => 0,
    'description' => '',
    'price' => 0,
    'image_path' => ''
];

if (!$is_new) {
    // Проверка ID услуги
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['errors' => ['general' => 'ID услуги не указан']]);
            exit;
        }
        die('ID услуги не указан');
    }
    $service_id = (int)$_GET['id'];

    // Загрузка услуги
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$service) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['errors' => ['general' => 'Услуга не найдена']]);
            exit;
        }
        die('Услуга не найдена');
    }
}

$success = '';
$error = '';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_editor = isset($_SESSION['role']) && $_SESSION['role'] === 'editor';
$is_worker = isset($_SESSION['role']) && $_SESSION['role'] === 'worker';

// AJAX-обработка
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($is_admin || $is_editor)) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Недостаточно прав для изменения услуги']]);
        exit;
    }
    $action = $_POST['action'] ?? '';
    $response = [];
    $errors = [];
    
    // Функция для обработки загрузки изображения
    function handle_image_upload($image_name_field = 'image_name', $image_file_field = 'image_file', $old_path = '') {
        $result = ['path' => $old_path, 'error' => '', 'deleted_old' => false];
        if (!isset($_FILES[$image_file_field]) || $_FILES[$image_file_field]['error'] === UPLOAD_ERR_NO_FILE) {
            // Нет нового файла — оставляем старый путь
            return $result;
        }
        $image_name = trim($_POST[$image_name_field] ?? '');
        if ($image_name === '') {
            $result['error'] = 'Укажите название изображения';
            return $result;
        }
        $file = $_FILES[$image_file_field];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $result['error'] = 'Разрешены только jpg, png, webp';
            return $result;
        }
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/services/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . $image_name . '.' . $ext;
        $rel_path = 'images/services/' . $image_name . '.' . $ext;
        // Если старый файл существует и отличается по расширению — удаляем
        if ($old_path && $old_path !== $rel_path) {
            $old_file = $_SERVER['DOCUMENT_ROOT'] . '/' . $old_path;
            if (file_exists($old_file)) {
                @unlink($old_file);
                $result['deleted_old'] = true;
            }
        }
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            $result['error'] = 'Ошибка при сохранении файла';
            return $result;
        }
        $result['path'] = $rel_path;
        return $result;
    }
    
    if ($action === 'edit_service' || $action === 'add_service') {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $image_name = trim($_POST['image_name'] ?? '');
        $image_path = '';
        if ($action === 'edit_service' && !$is_new) {
            $image_path = $service['image_path'];
        }
        // Обработка файла
        $img_result = handle_image_upload('image_name', 'image_file', $image_path);
        if ($img_result['error']) {
            $errors['general'] = $img_result['error'];
        } else {
            $image_path = $img_result['path'];
        }
        // --- Переименование файла, если изменено только название ---
        if ($action === 'edit_service' && !$is_new && !$img_result['deleted_old'] && !$img_result['error']) {
            $old_path = $service['image_path'];
            $old_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/services/';
            $old_ext = pathinfo($old_path, PATHINFO_EXTENSION);
            $new_path = 'images/services/' . $image_name . '.' . $old_ext;
            $old_file = $_SERVER['DOCUMENT_ROOT'] . '/' . $old_path;
            $new_file = $old_dir . $image_name . '.' . $old_ext;
            if ($old_path && $image_name && $old_path !== $new_path && file_exists($old_file)) {
                if (file_exists($new_file)) {
                    $errors['general'] = 'Файл с новым именем уже существует';
                } else {
                    if (rename($old_file, $new_file)) {
                        $image_path = $new_path;
                    } else {
                        $errors['general'] = 'Ошибка при переименовании файла';
                    }
                }
            }
        }
        if ($name === '' || $category_id <= 0 || $description === '' || $price <= 0) {
            $errors['general'] = 'Пожалуйста, заполните все поля корректно';
        }
        if (!$errors) {
            if ($action === 'edit_service' && !$is_new) {
                $stmt = $pdo->prepare('UPDATE services SET name=?, category_id=?, description=?, price=?, image_path=? WHERE id=?');
                if ($stmt->execute([$name, $category_id, $description, $price, $image_path, $service_id])) {
                    $response['success'] = 'Услуга успешно обновлена';
                    $response['service_data'] = [
                        'name' => $name,
                        'category_id' => $category_id,
                        'description' => $description,
                        'price' => $price,
                        'image_path' => $image_path,
                        'category_name' => ''
                    ];
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $category_id) {
                            $response['service_data']['category_name'] = $cat['name'];
                            break;
                        }
                    }
                } else {
                    $errors['general'] = 'Ошибка при обновлении услуги';
                }
            } else if ($action === 'add_service' || ($action === 'edit_service' && $is_new)) {
                $stmt = $pdo->prepare('INSERT INTO services (name, category_id, description, price, image_path) VALUES (?, ?, ?, ?, ?)');
                if ($stmt->execute([$name, $category_id, $description, $price, $image_path])) {
                    $response['success'] = 'Услуга успешно создана';
                    $response['redirect'] = '/admin/services.php';
                } else {
                    $errors['general'] = 'Ошибка при создании услуги';
                }
            }
        }
        if ($errors) {
            header('Content-Type: application/json');
            echo json_encode(['errors' => $errors]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    
    if ($action === 'delete_service' && !$is_new) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE service_id = ?');
        $stmt->execute([$service_id]);
        $req_count = $stmt->fetchColumn();
        if ($req_count > 0) {
            $errors['general'] = 'Нельзя удалить услугу, к которой привязаны заявки!';
        } else {
            // Удаляем изображение, если есть
            if (!empty($service['image_path'])) {
                $img_file = $_SERVER['DOCUMENT_ROOT'] . '/' . $service['image_path'];
                if (file_exists($img_file)) {
                    @unlink($img_file);
                }
            }
            $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
            if ($stmt->execute([$service_id])) {
                $response['success'] = 'Услуга успешно удалена';
                $response['redirect'] = '/admin/services.php?deleted=1';
            } else {
                $errors['general'] = 'Ошибка при удалении услуги';
            }
        }
        if ($errors) {
            header('Content-Type: application/json');
            echo json_encode(['errors' => $errors]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_new ? 'Добавление услуги' : 'Редактирование услуги' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin/edit_service.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= $is_new ? 'Добавление услуги' : 'Услуга: просмотр и редактирование' ?></h1>
        <a href="/admin/services.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад к услугам
        </a>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div id="notification" class="alert" style="display:none;"></div>
    
    <?php if ($is_new): ?>
        <?php if ($is_admin || $is_editor): ?>
        <!-- Режим добавления: сразу показываем форму -->
        <form method="post" id="service-edit-form" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Название</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Категория</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Цена (₽)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Название изображения (без расширения)</label>
                <input type="text" name="image_name" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Файл изображения (jpg, png, webp)</label>
                <input type="file" name="image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" name="add_service" value="1" class="btn btn-primary">Добавить услугу</button>
                    <a href="/admin/services.php" class="btn btn-secondary">Отмена</a>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-info">Только просмотр. Недостаточно прав для добавления услуги.</div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Режим редактирования: сначала просмотр, потом форма -->
        <div id="service-view">
            <dl class="row">
                <dt class="col-sm-3">Название</dt>
                <dd class="col-sm-9" id="view-name"><?= htmlspecialchars($service['name']) ?></dd>
                <dt class="col-sm-3">Категория</dt>
                <dd class="col-sm-9" id="view-category">
                    <?php foreach ($categories as $cat) {
                        if ($cat['id'] == $service['category_id']) {
                            echo htmlspecialchars($cat['name']);
                            break;
                        }
                    } ?>
                </dd>
                <dt class="col-sm-3">Описание</dt>
                <dd class="col-sm-9" id="view-description"><?= nl2br(htmlspecialchars($service['description'])) ?></dd>
                <dt class="col-sm-3">Цена</dt>
                <dd class="col-sm-9" id="view-price"><?= number_format($service['price'], 2, ',', ' ') ?> ₽</dd>
                <dt class="col-sm-3">Изображение</dt>
                <dd class="col-sm-9" id="view-image-path">
                    <?php if ($service['image_path']): ?>
                        <img src="/<?= htmlspecialchars($service['image_path']) ?>" alt="" style="max-width:120px;max-height:120px;">
                        <div><?= htmlspecialchars($service['image_path']) ?></div>
                    <?php else: ?>
                        <span class="text-muted">Нет изображения</span>
                    <?php endif; ?>
                </dd>
            </dl>
            <?php if ($is_admin || $is_editor): ?>
            <button class="btn btn-outline-primary" id="edit-service-btn"><i class="bi bi-pencil"></i> Редактировать</button>
            <?php endif; ?>
        </div>
        <?php if ($is_admin || $is_editor): ?>
        <form method="post" id="service-edit-form" style="display:none;" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_service">
            <div class="mb-3">
                <label class="form-label">Название</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($service['name']) ?>" required disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Категория</label>
                <select name="category_id" class="form-select" required disabled>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $service['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" required disabled><?= htmlspecialchars($service['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Цена (₽)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($service['price']) ?>" required disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Название изображения (без расширения)</label>
                <input type="text" name="image_name" class="form-control" value="<?= $service['image_path'] ? pathinfo($service['image_path'], PATHINFO_FILENAME) : '' ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Файл изображения (jpg, png, webp)</label>
                <input type="file" name="image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp" disabled>
            </div>
            <?php if ($service['image_path']): ?>
            <div class="mb-3" id="edit-image-preview">
                <label class="form-label">Текущее изображение</label><br>
                <img src="/<?= htmlspecialchars($service['image_path']) ?>" alt="" style="max-width:120px;max-height:120px;">
                <div><?= htmlspecialchars($service['image_path']) ?></div>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" name="edit_service" class="btn btn-primary" disabled>Сохранить</button>
                    <button type="button" class="btn btn-secondary" id="cancel-edit-btn">Отмена</button>
                </div>
                <button type="button" class="btn btn-danger" id="delete-service-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" style="display:none;">Удалить</button>
            </div>
        </form>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Модальное окно подтверждения удаления -->
    <?php if (!$is_new): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteModalLabel">Удалить услугу</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="delete_service" value="1">
              <p>Вы действительно хотите удалить услугу <b><?= htmlspecialchars($service['name']) ?></b>?</p>
              <p class="text-danger small mb-0">Удалить можно только если к услуге не привязаны заявки.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-danger">Удалить</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 