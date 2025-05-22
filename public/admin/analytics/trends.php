<?php
require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $where = [];
    $params = [];
    if (!empty($_GET['date_from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // --- 1. Месяцы с наибольшим количеством заявок (за год или по фильтру) ---
    $months = [];
    if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
        $start = new DateTime(date('Y-m-01', strtotime('-11 months')));
        $end = new DateTime(date('Y-m-01'));
        for ($d = clone $start; $d <= $end; $d->modify('+1 month')) {
            $months[$d->format('Y-m')] = 0;
        }
    }
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt FROM requests $whereSql GROUP BY ym ORDER BY ym";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw = $stmt->fetchAll();
    foreach ($raw as $row) {
        $months[$row['ym']] = (int)$row['cnt'];
    }
    $monthlyStats = [];
    foreach ($months as $ym => $cnt) {
        $monthlyStats[] = ['ym' => $ym, 'count' => $cnt];
    }
    // Топ-3 месяца
    $topMonths = $monthlyStats;
    usort($topMonths, function($a, $b) { return $b['count'] <=> $a['count']; });
    $topMonths = array_slice($topMonths, 0, 3);

    // --- 2. Популярные услуги по сезонам ---
    // Сезоны: зима (12,1,2), весна (3,4,5), лето (6,7,8), осень (9,10,11)
    $seasons = [
        'winter' => [12,1,2],
        'spring' => [3,4,5],
        'summer' => [6,7,8],
        'autumn' => [9,10,11],
    ];
    $seasonNames = [
        'winter' => 'Зима',
        'spring' => 'Весна',
        'summer' => 'Лето',
        'autumn' => 'Осень',
    ];
    $topServicesBySeason = [];
    foreach ($seasons as $season => $monthsArr) {
        $placeholders = implode(',', array_fill(0, count($monthsArr), '?'));
        $paramsSeason = $params;
        foreach ($monthsArr as $m) $paramsSeason[] = $m;
        $sql = "SELECT s.name, COUNT(r.id) as cnt
                FROM requests r
                JOIN services s ON r.service_id = s.id
                WHERE ".($where ? implode(' AND ', $where).' AND ' : '')."MONTH(r.created_at) IN ($placeholders)
                GROUP BY s.id, s.name
                ORDER BY cnt DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsSeason);
        $topServicesBySeason[$season] = $stmt->fetchAll();
    }

    echo json_encode([
        'monthlyStats' => $monthlyStats,
        'topMonths' => $topMonths,
        'topServicesBySeason' => $topServicesBySeason,
        'seasonNames' => $seasonNames,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сезонность и тренды | Админ-панель</title>
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
        <h1 class="mb-0">Сезонность и тренды</h1>
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
                    <h6 class="card-title">Топ-3 месяца по заявкам</h6>
                    <ul class="list-unstyled mb-0" id="top-months-list"></ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Популярные услуги по сезонам</h6>
                    <div class="d-flex justify-content-center mb-2" id="season-tabs"></div>
                    <ul class="list-unstyled mb-0" id="top-services-list"></ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title">Заявки по месяцам</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="requestsLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Топ-5 услуг по сезонам</h6>
                    <div class="chart-container d-flex justify-content-center align-items-center">
                        <canvas id="seasonBarChart" style="max-width:700px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/trends.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 