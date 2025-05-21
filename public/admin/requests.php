<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth_check.php';  // Проверка доступа (admin, worker, editor)
require_once '../includes/db.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Получение списка услуг для фильтра
$stmt = $pdo->query('SELECT id, name FROM services ORDER BY name');
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка фильтров и сортировки
$status = $_GET['status'] ?? 'all';
$service_id = (int)($_GET['service_id'] ?? 0);

// Установка значений дат по умолчанию и ограничений
$default_date_from = date('Y-m-d', strtotime('-3 months'));
$default_date_to = date('Y-m-d');
$date_from = $_GET['date_from'] ?? $default_date_from;
$date_to = $_GET['date_to'] ?? $default_date_to;

$allowed_sort_fields = ['id', 'status', 'created_at', 'user_name'];
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

// Подготовка условий WHERE
$where_conditions = [];
$params = [];

if ($status !== 'all') {
    $where_conditions[] = 'r.status = :status';
    $params['status'] = $status;
} 

if ($service_id > 0) {
    $where_conditions[] = 'r.service_id = :service_id';
    $params['service_id'] = $service_id;
}

$where_conditions[] = 'r.created_at BETWEEN :date_from AND :date_to';
$params['date_from'] = $date_from . ' 00:00:00';
$params['date_to'] = $date_to . ' 23:59:59';

// Формирование WHERE части запроса
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Безопасная обработка сортировки
$sort_mapping = [
    'id' => 'r.id',
    'user_name' => 'COALESCE(u.name, r.name)',
    'service_name' => 's.name',
    'status' => 'r.status',
    'created_at' => 'r.created_at'
];

// Получаем клиентское значение sort_field
$client_sort_field = isset($_GET['sort_field']) && isset($sort_mapping[$_GET['sort_field']]) 
    ? $_GET['sort_field'] 
    : 'created_at';

// Получаем SQL значение для сортировки
$sql_sort_field = $sort_mapping[$client_sort_field];

$sql = "
    SELECT 
        r.id, 
        COALESCE(u.name, r.name) AS user_name, 
        COALESCE(u.email, r.email) AS user_email, 
        s.name AS service_name, 
        r.status, 
        r.created_at, 
        r.description
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    JOIN services s ON r.service_id = s.id
    {$where_clause}
    ORDER BY {$sql_sort_field} {$sort_order}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['requests' => $requests]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заявками</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sort-btn {
            padding: 0 4px;
            color: #6c757d;
            text-decoration: none;
            cursor: pointer;
        }
        .sort-btn:hover {
            color: #000;
        }
        .sort-btn.active {
            color: #0d6efd;
        }
        .filter-btn {
            padding: 0 4px;
            color: #6c757d;
            cursor: pointer;
        }
        .filter-btn:hover {
            color: #000;
        }
        .filter-btn.active {
            color: #0d6efd;
        }
        .filter-popup {
            position: absolute;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        .column-header {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .column-title {
            margin-right: auto;
        }
    </style>
    <script>
        // Передаем начальные значения в JavaScript
        const initialState = {
            status: <?php echo json_encode($status); ?>,
            service_id: <?php echo json_encode($service_id); ?>,
            date_from: <?php echo json_encode($date_from); ?>,
            date_to: <?php echo json_encode($date_to); ?>,
            sort_field: <?php echo json_encode($client_sort_field); ?>,
            sort_order: <?php echo json_encode($sort_order); ?>
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление заявками</h1>
            <a href="/admin/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Таблица заявок -->
        <div id="requests-table-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>
                            <div class="column-header">
                                <span class="column-title">ID</span>
                                <a class="sort-btn<?php echo $client_sort_field === 'id' && $sort_order === 'ASC' ? ' active' : ''; ?>" data-field="id" data-order="ASC">
                                    <i class="bi bi-arrow-up"></i>
                                </a>
                                <a class="sort-btn<?php echo $client_sort_field === 'id' && $sort_order === 'DESC' ? ' active' : ''; ?>" data-field="id" data-order="DESC">
                                    <i class="bi bi-arrow-down"></i>
                                </a>
                            </div>
                        </th>
                        <th>
                            <div class="column-header">
                                <span class="column-title">Пользователь</span>
                                <a class="sort-btn" data-field="user_name" data-order="ASC">
                                    <i class="bi bi-arrow-up"></i>
                                </a>
                                <a class="sort-btn" data-field="user_name" data-order="DESC">
                                    <i class="bi bi-arrow-down"></i>
                                </a>
                            </div>
                        </th>
                        <th>
                            <div class="column-header">
                                <span class="column-title">Услуга</span>
                                <a class="filter-btn" data-filter="service">
                                    <i class="bi bi-funnel"></i>
                                </a>
                                <div id="service-filter" class="filter-popup">
                                    <select id="filter-service" class="form-select form-select-sm">
                                        <option value="0">Все услуги</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['id']; ?>" <?php echo $service_id === $service['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </th>
                        <th>
                            <div class="column-header">
                                <span class="column-title">Статус</span>
                                <a class="filter-btn" data-filter="status">
                                    <i class="bi bi-funnel"></i>
                                </a>
                                <a class="sort-btn<?php echo $client_sort_field === 'status' && $sort_order === 'ASC' ? ' active' : ''; ?>" data-field="status" data-order="ASC">
                                    <i class="bi bi-arrow-up"></i>
                                </a>
                                <a class="sort-btn<?php echo $client_sort_field === 'status' && $sort_order === 'DESC' ? ' active' : ''; ?>" data-field="status" data-order="DESC">
                                    <i class="bi bi-arrow-down"></i>
                                </a>
                                <div id="status-filter" class="filter-popup">
                                    <select id="filter-status" class="form-select form-select-sm">
                                        <option value="all">Все статусы</option>
                                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Новая</option>
                                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                    </select>
                                </div>
                            </div>
                        </th>
                        <th>
                            <div class="column-header">
                                <span class="column-title">Дата</span>
                                <a class="filter-btn" data-filter="date">
                                    <i class="bi bi-calendar3"></i>
                                </a>
                                <a class="sort-btn<?php echo $client_sort_field === 'created_at' && $sort_order === 'ASC' ? ' active' : ''; ?>" data-field="created_at" data-order="ASC">
                                    <i class="bi bi-arrow-up"></i>
                                </a>
                                <a class="sort-btn<?php echo $client_sort_field === 'created_at' && $sort_order === 'DESC' ? ' active' : ''; ?>" data-field="created_at" data-order="DESC">
                                    <i class="bi bi-arrow-down"></i>
                                </a>
                                <div id="date-filter" class="filter-popup">
                                    <div class="mb-2">
                                        <label class="form-label">От:</label>
                                        <input type="date" id="filter-date-from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">До:</label>
                                        <input type="date" id="filter-date-to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th>
                            <div class="column-header">
                                <span class="column-title">Описание</span>
                            </div>
                        </th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="requests-table-body">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Заявки не найдены</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['user_name'] ?: $request['user_email']); ?></td>
                                <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                                <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                                <td><?php echo htmlspecialchars(substr($request['description'] ?: '', 0, 100)) . (strlen($request['description'] ?: '') > 100 ? '...' : ''); ?></td>
                                <td>
                                    <a href="/admin/request.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm">Просмотр</a>
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