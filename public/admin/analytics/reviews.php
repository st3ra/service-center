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

    // --- Метрики ---
    // 1. Общее количество отзывов
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews");
    $stmt->execute();
    $total = $stmt->fetchColumn();

    // 2. Количество отзывов за период
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews $whereSql");
    $stmt->execute($params);
    $countPeriod = $stmt->fetchColumn();

    // 3. Средняя длина отзыва (в символах)
    $stmt = $pdo->prepare("SELECT AVG(CHAR_LENGTH(text)) FROM reviews $whereSql");
    $stmt->execute($params);
    $avgLength = round($stmt->fetchColumn());

    // --- График по неделям ---
    // Берём за последний год или по фильтру
    $chartWhere = $where;
    $chartParams = $params;
    if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
        $chartWhere[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
    }
    $chartWhereSql = $chartWhere ? ('WHERE ' . implode(' AND ', $chartWhere)) : '';
    $sql = "SELECT YEARWEEK(created_at, 1) as yw, MIN(DATE(created_at)) as week_start, COUNT(*) as count
            FROM reviews $chartWhereSql
            GROUP BY yw
            ORDER BY week_start";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($chartParams);
    $weeklyStats = $stmt->fetchAll();

    // --- Последние 5 отзывов ---
    $stmt = $pdo->prepare("SELECT author, text, created_at FROM reviews $whereSql ORDER BY created_at DESC LIMIT 5");
    $stmt->execute($params);
    $lastReviews = $stmt->fetchAll();

    echo json_encode([
        'total' => (int)$total,
        'countPeriod' => (int)$countPeriod,
        'avgLength' => (int)$avgLength,
        'weeklyStats' => $weeklyStats,
        'lastReviews' => $lastReviews,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика отзывов | Админ-панель</title>
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
        .review-text {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Аналитика отзывов</h1>
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
                    <h6 class="card-title">Всего отзывов</h6>
                    <div class="display-6" id="total-reviews">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">За выбранный период</h6>
                    <div class="display-6" id="reviews-period">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Средняя длина отзыва</h6>
                    <div class="display-6" id="avg-length">...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title">Динамика новых отзывов по неделям</h6>
                    <div class="chart-container">
                        <canvas id="reviewsLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Последние 5 отзывов</h6>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Автор</th>
                                    <th>Текст</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody id="last-reviews-table">
                                <tr><td colspan="3" class="text-center">Загрузка...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="../analytics.php" class="btn btn-secondary">← Назад к аналитике</a>
</div>
<script src="/assets/js/admin/analytics/reviews.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 