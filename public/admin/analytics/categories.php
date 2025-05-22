<?php
require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $whereRequests = [];
    $paramsRequests = [];
    if (!empty($_GET['date_from'])) {
        $whereRequests[] = 'created_at >= ?';
        $paramsRequests[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $whereRequests[] = 'created_at <= ?';
        $paramsRequests[] = $_GET['date_to'] . ' 23:59:59';
    }
    $whereRequestsSql = $whereRequests ? ('WHERE ' . implode(' AND ', $whereRequests)) : '';

    // --- 1. Количество услуг в каждой категории ---
    $sql = "SELECT c.id, c.name, COUNT(s.id) as services_count
            FROM categories c
            LEFT JOIN services s ON s.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name";
    $servicesByCategory = $pdo->query($sql)->fetchAll();

    // --- 2. Количество заявок по категориям ---
    $sql = "SELECT c.id, c.name, COUNT(r.id) as requests_count
            FROM categories c
            LEFT JOIN services s ON s.category_id = c.id
            LEFT JOIN requests r ON r.service_id = s.id 
            $whereRequestsSql
            GROUP BY c.id, c.name
            ORDER BY c.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsRequests);
    $requestsByCategory = $stmt->fetchAll();

    // --- 3. Данные для столбчатой диаграммы (заявки по категориям) ---
    $barChart = $requestsByCategory;

    // --- 4. Данные для круговой диаграммы (распределение услуг по категориям) ---
    $sql = "SELECT c.name, COUNT(s.id) as services_count
            FROM categories c
            LEFT JOIN services s ON s.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name";
    $pieChart = $pdo->query($sql)->fetchAll();

    // --- 5. Таблица: категории без услуг или с <5 заявок ---
    $sql = "SELECT c.name, COUNT(s.id) as services_count, COUNT(r.id) as requests_count
            FROM categories c
            LEFT JOIN services s ON s.category_id = c.id
            LEFT JOIN requests r ON r.service_id = s.id 
            $whereRequestsSql
            GROUP BY c.id, c.name
            HAVING services_count = 0 OR requests_count < 5
            ORDER BY c.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsRequests);
    $fewRequests = $stmt->fetchAll();

    echo json_encode([
        'servicesByCategory' => $servicesByCategory,
        'requestsByCategory' => $requestsByCategory,
        'barChart' => $barChart,
        'pieChart' => $pieChart,
        'fewRequests' => $fewRequests,
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
    <title>Аналитика по категориям | Админ-панель</title>
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
        <h1 class="mb-0">Аналитика по категориям</h1>
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
    </form>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Услуг в категориях</h6>
                    <ul class="list-unstyled mb-0 small" id="services-by-category-list" style="text-align:left"></ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Заявок по категориям</h6>
                    <ul class="list-unstyled mb-0 small" id="requests-by-category-list" style="text-align:left"></ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Количество заявок по категориям</h6>
                    <div class="chart-container">
                        <canvas id="requestsBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Распределение услуг по категориям</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="servicesPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Категории без услуг или с &lt;5 заявок</h6>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Услуг</th>
                            <th>Заявок</th>
                        </tr>
                    </thead>
                    <tbody id="categories-few-requests-table">
                        <tr><td colspan="3" class="text-center">Загрузка...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/categories.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 