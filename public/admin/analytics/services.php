<?php
require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'categories') {
    header('Content-Type: application/json; charset=utf-8');
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    echo json_encode($categories);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $where = [];
    $params = [];
    // Дата от
    if (!empty($_GET['date_from'])) {
        $where[] = 'r.created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    // Дата до
    if (!empty($_GET['date_to'])) {
        $where[] = 'r.created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    // Категории (мультивыбор)
    $catIds = [];
    if (isset($_GET['category'])) {
        if (is_array($_GET['category'])) {
            foreach ($_GET['category'] as $catId) {
                if ($catId !== '') $catIds[] = (int)$catId;
            }
        } elseif ($_GET['category'] !== '') {
            $catIds[] = (int)$_GET['category'];
        }
        if (count($catIds) > 0) {
            $in = implode(',', array_fill(0, count($catIds), '?'));
            $where[] = 's.category_id IN (' . $in . ')';
            $params = array_merge($params, $catIds);
        }
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // --- 1. Топ-5 популярных услуг по количеству заявок ---
    $sql = "SELECT s.id, s.name, COUNT(r.id) as requests_count
            FROM services s
            LEFT JOIN requests r ON r.service_id = s.id
            $whereSql
            GROUP BY s.id, s.name
            ORDER BY requests_count DESC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $topServices = $stmt->fetchAll();

    // --- 2. Выручка по завершённым заявкам (status = 'completed') ---
    $revenueWhere = $where;
    $revenueParams = $params;
    $revenueWhere[] = "r.status = 'completed'";
    $revenueWhereSql = $revenueWhere ? ('WHERE ' . implode(' AND ', $revenueWhere)) : '';
    $sql = "SELECT SUM(s.price) as revenue
            FROM requests r
            JOIN services s ON r.service_id = s.id
            $revenueWhereSql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($revenueParams);
    $revenue = $stmt->fetchColumn();
    if ($revenue === null) $revenue = 0;

    // --- 3. Услуги без заявок ---
    $sql = "SELECT s.id, s.name, s.price, c.name as category
            FROM services s
            LEFT JOIN requests r ON r.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            $whereSql
            GROUP BY s.id, s.name, s.price, c.name
            HAVING COUNT(r.id) = 0
            ORDER BY s.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $servicesNoRequests = $stmt->fetchAll();

    // --- 4. Средняя цена услуги по категориям ---
    $sql = "SELECT c.name as category, ROUND(AVG(s.price), 2) as avg_price
            FROM services s
            JOIN categories c ON s.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name";
    $avgPrices = $pdo->query($sql)->fetchAll();

    // --- 5. Данные для столбчатой диаграммы (топ-10 услуг по заявкам) ---
    $sql = "SELECT s.name, COUNT(r.id) as requests_count
            FROM services s
            LEFT JOIN requests r ON r.service_id = s.id
            $whereSql
            GROUP BY s.id, s.name
            ORDER BY requests_count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $barChart = $stmt->fetchAll();

    // --- 6. Данные для круговой диаграммы (доля выручки по категориям) ---
    $pieWhere = ["r.status = 'completed'"];
    $pieParams = [];
    if (!empty($_GET['date_from'])) {
        $pieWhere[] = 'r.created_at >= ?';
        $pieParams[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $pieWhere[] = 'r.created_at <= ?';
        $pieParams[] = $_GET['date_to'] . ' 23:59:59';
    }
    // Категории (мультивыбор)
    $pieCatIds = [];
    if (isset($_GET['category'])) {
        if (is_array($_GET['category'])) {
            foreach ($_GET['category'] as $catId) {
                if ($catId !== '') $pieCatIds[] = (int)$catId;
            }
        } elseif ($_GET['category'] !== '') {
            $pieCatIds[] = (int)$_GET['category'];
        }
        if (count($pieCatIds) > 0) {
            $in = implode(',', array_fill(0, count($pieCatIds), '?'));
            $pieWhere[] = 's.category_id IN (' . $in . ')';
            $pieParams = array_merge($pieParams, $pieCatIds);
        }
    }
    $pieWhereSql = $pieWhere ? ('WHERE ' . implode(' AND ', $pieWhere)) : '';
    $sql = "SELECT c.name as category, SUM(s.price) as revenue
            FROM requests r
            JOIN services s ON r.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            $pieWhereSql
            GROUP BY c.id, c.name ORDER BY revenue DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($pieParams);
    $pieChart = $stmt->fetchAll();

    echo json_encode([
        'topServices' => $topServices,
        'revenue' => (float)$revenue,
        'servicesNoRequests' => $servicesNoRequests,
        'avgPrices' => $avgPrices,
        'barChart' => $barChart,
        'pieChart' => $pieChart,
    ]);
    exit;
}

// Заглушка для будущих AJAX-обработчиков (action=stats и т.д.)
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика по услугам | Админ-панель</title>
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
        /* Категории: dropdown по ширине кнопки */
        #categoryDropdown + .dropdown-menu {
            width: 100%;
            min-width: unset;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="../analytics.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад к аналитике</a>
        <h1 class="mb-0">Аналитика по услугам</h1>
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
        <div class="col-md-3">
            <label class="form-label">Категории</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Выбрать категории
                </button>
                <div class="dropdown-menu p-3" style="min-width: 250px;" id="category-dropdown-menu">
                    <div id="category-checkboxes">
                        <div class="text-muted">Загрузка...</div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="apply-categories">Применить</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" id="reset-categories">Сбросить</button>
                    </div>
                </div>
            </div>
            <div class="form-text">Можно выбрать несколько</div>
        </div>
    </form>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Топ-5 популярных услуг</h6>
                    <ul class="list-unstyled mb-0 small" id="top-services-list" style="text-align:left"></ul>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Выручка (завершённые)</h6>
                    <div class="display-6" id="revenue-completed">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Услуг без заявок</h6>
                    <div class="display-6" id="services-no-requests">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Средняя цена по категориям</h6>
                    <ul class="list-unstyled mb-0 small" id="avg-price-list" style="text-align:left"></ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Топ-10 услуг по количеству заявок</h6>
                    <div class="chart-container">
                        <canvas id="servicesBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Доля выручки по категориям</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="revenuePieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title d-flex align-items-center justify-content-between">
                Услуги без заявок
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#servicesNoRequestsCollapse" aria-expanded="false" aria-controls="servicesNoRequestsCollapse">
                    Показать/Скрыть
                </button>
            </h6>
            <div class="collapse" id="servicesNoRequestsCollapse">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Услуга</th>
                                <th>Категория</th>
                                <th>Цена</th>
                            </tr>
                        </thead>
                        <tbody id="services-no-requests-table">
                            <tr><td colspan="3" class="text-center">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/services.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 