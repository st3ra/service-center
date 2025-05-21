<?php
require_once 'includes/auth_check.php';  // Проверка, что пользователь — администратор
require_once '../includes/db.php';       // Подключение к базе данных

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Обработка фильтров
$search = '%' . ($_GET['search'] ?? '') . '%';

// Подготовка и выполнение SQL-запроса
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.email, u.phone, u.role, COUNT(r.id) AS request_count
    FROM users u
    LEFT JOIN requests r ON u.id = r.user_id
    WHERE u.name LIKE ? OR u.email LIKE ?
    GROUP BY u.id
');
$stmt->execute([$search, $search]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['users' => $users]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin/utils.js"></script>
    <script src="/assets/js/admin/users.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление пользователями</h1>
            <a href="/admin/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Поле поиска -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           id="search-users" 
                           class="form-control" 
                           placeholder="Поиск по имени или email" 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Таблица пользователей -->
        <div id="users-table-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Роль</th>
                        <th>Заявки</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Пользователи не найдены</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo $user['request_count']; ?></td>
                                <td>
                                    <a href="/admin/user_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">Просмотр</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>