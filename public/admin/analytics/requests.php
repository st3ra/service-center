<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $where = [];
    $params = [];
    // Дата от
    if (!empty($_GET['date_from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    // Дата до
    if (!empty($_GET['date_to'])) {
        $where[] = 'created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    // Статус
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    // Категория
    if (!empty($_GET['category'])) {
        $where[] = 'service_id IN (SELECT id FROM services WHERE category_id = ?)';
        $params[] = $_GET['category'];
    }
    // Услуга
    if (!empty($_GET['service'])) {
        $where[] = 'service_id = ?';
        $params[] = $_GET['service'];
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // --- Метрики ---
    // 1. Общее количество заявок
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests $whereSql");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // 2. Распределение по статусам
    $statuses = ['new', 'in_progress', 'completed'];
    $statusCounts = [];
    foreach ($statuses as $status) {
        $whereStatus = $where;
        $paramsStatus = $params;
        $whereStatus[] = 'status = ?';
        $paramsStatus[] = $status;
        $whereStatusSql = $whereStatus ? ('WHERE ' . implode(' AND ', $whereStatus)) : '';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests $whereStatusSql");
        $stmt->execute($paramsStatus);
        $statusCounts[$status] = (int)$stmt->fetchColumn();
    }

    // 3. Количество заявок за неделю и месяц (игнорируют фильтр по дате, но учитывают остальные)
    $weekWhere = $where;
    $weekParams = $params;
    $weekWhere[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    $weekWhereSql = $weekWhere ? ('WHERE ' . implode(' AND ', $weekWhere)) : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests $weekWhereSql");
    $stmt->execute($weekParams);
    $week = $stmt->fetchColumn();

    $monthWhere = $where;
    $monthParams = $params;
    $monthWhere[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
    $monthWhereSql = $monthWhere ? ('WHERE ' . implode(' AND ', $monthWhere)) : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests $monthWhereSql");
    $stmt->execute($monthParams);
    $month = $stmt->fetchColumn();

    // 4. Динамика по дням
    // Если выбран диапазон дат — строим по нему, иначе — за последние 7 дней
    $dailyWhere = $where;
    $dailyParams = $params;
    if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
        $dailyWhere[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    }
    $dailyWhereSql = $dailyWhere ? ('WHERE ' . implode(' AND ', $dailyWhere)) : '';
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as count FROM requests $dailyWhereSql GROUP BY DATE(created_at) ORDER BY date";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($dailyParams);
    $dailyStats = $stmt->fetchAll();

    // 5. Топ-5 дней по количеству заявок (с учётом фильтров)
    $topSql = "SELECT DATE(created_at) as date, COUNT(*) as count FROM requests $whereSql GROUP BY DATE(created_at) ORDER BY count DESC LIMIT 5";
    $stmt = $pdo->prepare($topSql);
    $stmt->execute($params);
    $topDays = $stmt->fetchAll();

    // Для Pie chart: формируем labels с количеством
    $pieLabels = [
        'Новые (' . $statusCounts['new'] . ')',
        'В работе (' . $statusCounts['in_progress'] . ')',
        'Завершены (' . $statusCounts['completed'] . ')'
    ];

    echo json_encode([
        'total' => (int)$total,
        'requestsWeek' => (int)$week,
        'requestsMonth' => (int)$month,
        'statusDistribution' => [
            'new' => $statusCounts['new'],
            'in_progress' => $statusCounts['in_progress'],
            'completed' => $statusCounts['completed'],
        ],
        'pieLabels' => $pieLabels,
        'dailyStats' => $dailyStats,
        'topDays' => $topDays,
    ]);
    exit;
}

// Получение списка категорий
if (isset($_GET['action']) && $_GET['action'] === 'categories') {
    header('Content-Type: application/json; charset=utf-8');
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    echo json_encode($categories);
    exit;
}

// Получение списка услуг по категории
if (isset($_GET['action']) && $_GET['action'] === 'services' && isset($_GET['category_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $stmt = $pdo->prepare('SELECT id, name FROM services WHERE category_id = ? ORDER BY name');
    $stmt->execute([$_GET['category_id']]);
    $services = $stmt->fetchAll();
    echo json_encode($services);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика по заявкам | Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .metric-card {
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .chart-container {
            position: relative;
            height: 320px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="../analytics.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад к аналитике</a>
        <h1 class="mb-0">Аналитика по заявкам</h1>
        <div></div>
    </div>
    <form id="filters-form" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="date-from" class="form-label">Дата от</label>
            <input type="date" class="form-control" id="date-from" name="date_from">
        </div>
        <div class="col-md-3">
            <label for="date-to" class="form-label">Дата до</label>
            <input type="date" class="form-control" id="date-to" name="date_to">
        </div>
        <div class="col-md-2">
            <label for="status" class="form-label">Статус</label>
            <select class="form-select" id="status" name="status">
                <option value="">Все</option>
                <option value="new">Новые</option>
                <option value="in_progress">В работе</option>
                <option value="completed">Завершены</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="category" class="form-label">Категория</label>
            <select class="form-select" id="category" name="category">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="service" class="form-label">Услуга</label>
            <select class="form-select" id="service" name="service" disabled>
                <option value="">Все</option>
            </select>
        </div>
    </form>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Всего заявок</h6>
                    <div class="display-6" id="total-requests">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Заявок за неделю</h6>
                    <div class="display-6" id="requests-week">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Заявок за месяц</h6>
                    <div class="display-6" id="requests-month">...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Распределение по статусам</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Динамика новых заявок по дням</h6>
                    <div class="chart-container">
                        <canvas id="requestsLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Топ-5 дней по количеству заявок</h6>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Количество заявок</th>
                        </tr>
                    </thead>
                    <tbody id="top-days-table">
                        <tr><td colspan="2" class="text-center">Загрузка...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/requests.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 