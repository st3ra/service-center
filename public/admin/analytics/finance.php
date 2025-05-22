<?php
require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $where = ["r.status = 'completed'"];
    $params = [];
    if (!empty($_GET['date_from'])) {
        $where[] = 'r.created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'r.created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // --- Метрики ---
    // 1. Общая выручка
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(s.price),0) FROM requests r JOIN services s ON r.service_id = s.id $whereSql");
    $stmt->execute($params);
    $totalRevenue = $stmt->fetchColumn();
    if ($totalRevenue === null) $totalRevenue = 0;

    // 2. Средний чек
    $stmt = $pdo->prepare("SELECT AVG(s.price) FROM requests r JOIN services s ON r.service_id = s.id $whereSql");
    $stmt->execute($params);
    $avgCheck = $stmt->fetchColumn();
    if ($avgCheck === null) $avgCheck = 0;
    $avgCheck = round($avgCheck);

    // 3. Выручка по месяцам (за последний год или по фильтру)
    $chartWhere = $where;
    $chartParams = $params;
    $months = [];
    $monthlyRevenue = [];
    if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
        // За последний год
        $start = new DateTime(date('Y-m-01', strtotime('-11 months'))); // 12 месяцев назад, с начала месяца
        $end = new DateTime(date('Y-m-01'));
        for ($d = clone $start; $d <= $end; $d->modify('+1 month')) {
            $months[$d->format('Y-m')] = 0;
        }
        $chartWhere[] = 'r.created_at >= ?';
        $chartParams[] = $start->format('Y-m-01 00:00:00');
    } else {
        // По фильтру дат
        $dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01', strtotime('-11 months'));
        $dateTo = !empty($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
        $start = new DateTime(date('Y-m-01', strtotime($dateFrom)));
        $end = new DateTime(date('Y-m-01', strtotime($dateTo)));
        for ($d = clone $start; $d <= $end; $d->modify('+1 month')) {
            $months[$d->format('Y-m')] = 0;
        }
    }
    $chartWhereSql = $chartWhere ? ('WHERE ' . implode(' AND ', $chartWhere)) : '';
    $sql = "SELECT DATE_FORMAT(r.created_at, '%Y-%m') as ym, SUM(s.price) as revenue
            FROM requests r
            JOIN services s ON r.service_id = s.id
            $chartWhereSql
            GROUP BY ym
            ORDER BY ym";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($chartParams);
    $raw = $stmt->fetchAll();
    foreach ($raw as $row) {
        $months[$row['ym']] = (float)$row['revenue'];
    }
    foreach ($months as $ym => $revenue) {
        $monthlyRevenue[] = ['ym' => $ym, 'revenue' => $revenue];
    }

    // 4. Доля выручки по категориям
    $sql = "SELECT c.name as category, SUM(s.price) as revenue
            FROM requests r
            JOIN services s ON r.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            $whereSql
            GROUP BY c.id, c.name
            ORDER BY revenue DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categoryRevenue = $stmt->fetchAll();

    echo json_encode([
        'totalRevenue' => (float)$totalRevenue,
        'avgCheck' => (int)$avgCheck,
        'monthlyRevenue' => $monthlyRevenue,
        'categoryRevenue' => $categoryRevenue,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Финансовая аналитика | Админ-панель</title>
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
        <h1 class="mb-0">Финансовая аналитика</h1>
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
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Общая выручка</h6>
                    <div class="display-6" id="total-revenue">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Средний чек</h6>
                    <div class="display-6" id="avg-check">...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Выручка по месяцам</h6>
                    <div class="chart-container">
                        <canvas id="revenueLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Доля выручки по категориям</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="categoryPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/finance.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 