<?php
require_once '../../includes/db.php';

function getDateFilterSql(&$params) {
    $where = [];
    if (!empty($_GET['date_from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'created_at <= ?';
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    return $where ? ('WHERE ' . implode(' AND ', $where)) : '';
}

if (isset($_GET['type']) && $_GET['type'] === 'requests') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="requests_export_'.date('Ymd_His').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Услуга', 'Категория', 'Статус', 'Дата', 'Сумма'], ',', '"', '\\');
    $params = [];
    $whereSql = getDateFilterSql($params);
    $sql = "SELECT r.id, s.name as service, c.name as category, r.status, r.created_at, s.price
            FROM requests r
            JOIN services s ON r.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            $whereSql
            ORDER BY r.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['service'],
            $row['category'],
            $row['status'],
            $row['created_at'],
            $row['price']
        ], ',', '"', '\\');
    }
    fclose($output);
    exit;
}

if (isset($_GET['type']) && $_GET['type'] === 'reviews') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reviews_export_'.date('Ymd_His').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Автор', 'Текст', 'Дата'], ',', '"', '\\');
    $params = [];
    $whereSql = getDateFilterSql($params);
    $sql = "SELECT author, text, created_at FROM reviews $whereSql ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['author'],
            $row['text'],
            $row['created_at']
        ], ',', '"', '\\');
    }
    fclose($output);
    exit;
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт данных | Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Экспорт данных</h1>
    <form class="row g-3 mb-4" id="export-form" onsubmit="return false;">
        <div class="col-md-3">
            <label for="date-from" class="form-label">Дата от</label>
            <input type="date" class="form-control" id="date-from" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label for="date-to" class="form-label">Дата до</label>
            <input type="date" class="form-control" id="date-to" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <button type="button" class="btn btn-primary me-2" id="export-requests">Экспорт заявок в CSV</button>
            <button type="button" class="btn btn-secondary" id="export-reviews">Экспорт отзывов в CSV</button>
        </div>
    </form>
    <a href="../analytics.php" class="btn btn-outline-secondary">← Назад к аналитике</a>
</div>
<script>
// JS для экспорта с учётом фильтров
function getExportParams() {
    const params = new URLSearchParams();
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    return params.toString();
}
document.getElementById('export-requests').onclick = function() {
    const params = getExportParams();
    window.location = 'export.php?type=requests' + (params ? '&' + params : '');
};
document.getElementById('export-reviews').onclick = function() {
    const params = getExportParams();
    window.location = 'export.php?type=reviews' + (params ? '&' + params : '');
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 