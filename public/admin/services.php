<?php
require_once 'includes/auth_check.php';
require_once '../includes/db.php';

// Получение списка категорий для фильтра
$cat_stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

if (session_status() === PHP_SESSION_NONE) session_start();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_editor = isset($_SESSION['role']) && $_SESSION['role'] === 'editor';
$is_worker = isset($_SESSION['role']) && $_SESSION['role'] === 'worker';

// Ajax добавление услуги
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_service') {
    if (!($is_admin || $is_editor)) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Недостаточно прав для добавления услуги']]);
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $image_path = trim($_POST['image_path'] ?? '');
    $errors = [];
    if ($name === '' || $category_id <= 0 || $description === '' || $price <= 0) {
        $errors['general'] = 'Пожалуйста, заполните все поля корректно';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO services (name, category_id, description, price, image_path) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$name, $category_id, $description, $price, $image_path])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => 'Услуга успешно добавлена']);
            exit;
        } else {
            $errors['general'] = 'Ошибка при добавлении услуги';
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['errors' => $errors]);
    exit;
}

// AJAX-поиск по названию услуги
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $where = '';
    $params = [];
    if ($category_id > 0) {
        $where = 'WHERE s.category_id = :category_id';
        $params['category_id'] = $category_id;
    }
    if ($search !== '') {
        $where .= ($where ? ' AND' : 'WHERE') . ' s.name LIKE :search';
        $params['search'] = '%' . $search . '%';
    }
    $sql = "
        SELECT s.id, s.name, c.name AS category_name, s.description, s.price, s.image_path
        FROM services s
        JOIN categories c ON s.category_id = c.id
        $where
        ORDER BY s.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['services' => $services]);
    exit;
}

// Фильтр по категории
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$where = '';
$params = [];
if ($category_id > 0) {
    $where = 'WHERE s.category_id = :category_id';
    $params['category_id'] = $category_id;
}

// Получение списка услуг с названием категории
$sql = "
    SELECT s.id, s.name, c.name AS category_name, s.description, s.price, s.image_path
    FROM services s
    JOIN categories c ON s.category_id = c.id
    $where
    ORDER BY s.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Услуги</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Услуги</h1>
            <a href="/admin/categories.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Категории
            </a>
        </div>
        <form method="get" class="mb-3 row g-2 align-items-center">
            <div class="col-auto">
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" id="search-services" class="form-control" placeholder="Поиск по названию...">
            </div>
        </form>
        <div class="mb-3">
            <?php if ($is_admin || $is_editor): ?>
            <a href="/admin/edit_service.php?new=1" class="btn btn-primary">
                <i class="bi bi-plus"></i> Добавить услугу
            </a>
            <?php endif; ?>
        </div>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Описание</th>
                    <th>Цена</th>
                    <th>Изображение</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="services-table-body">
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Услуги не найдены</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $srv): ?>
                        <tr>
                            <td><?= $srv['id'] ?></td>
                            <td><?= htmlspecialchars($srv['name']) ?></td>
                            <td><?= htmlspecialchars($srv['category_name']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($srv['description'], 0, 80, '...')) ?></td>
                            <td><?= number_format($srv['price'], 2, ',', ' ') ?> ₽</td>
                            <td><?= $srv['image_path'] ? htmlspecialchars($srv['image_path']) : '-' ?></td>
                            <td>
                                <?php if ($is_admin || $is_editor): ?>
                                <a href="/admin/edit_service.php?id=<?= $srv['id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Редактировать
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        window.isAdminOrEditor = <?= ($is_admin || $is_editor) ? 'true' : 'false' ?>;
    </script>
    <script src="/assets/js/admin/search_services.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
