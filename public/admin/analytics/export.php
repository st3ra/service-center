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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="../analytics.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад к аналитике</a>
        <h1 class="mb-0">Экспорт данных</h1>
        <div></div>
    </div>
    <form class="row g-3 mb-2" id="export-form" onsubmit="return false;">
        <div class="col-md-3">
            <label for="date-from" class="form-label">Дата от</label>
            <input type="date" class="form-control" id="date-from" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label for="date-to" class="form-label">Дата до</label>
            <input type="date" class="form-control" id="date-to" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
    </form>
    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <button type="button" class="btn btn-primary w-100" id="export-requests">Экспорт заявок в CSV</button>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-secondary w-100" id="export-reviews">Экспорт отзывов в CSV</button>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-success w-100" id="export-all-pdf">Экспортировать всю аналитику в PDF</button>
        </div>
    </div>
    <div id="export-status" class="mt-3"></div>
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
document.getElementById('export-all-pdf').onclick = async function() {
    const statusDiv = document.getElementById('export-status');
    statusDiv.textContent = 'Формируется PDF-отчёт, пожалуйста, подождите...';
    const date_from = document.getElementById('date-from').value;
    const date_to = document.getElementById('date-to').value;
    try {
        const params = [];
        if (date_from) params.push('date_from=' + encodeURIComponent(date_from));
        if (date_to) params.push('date_to=' + encodeURIComponent(date_to));
        const query = params.length ? ('&' + params.join('&')) : '';
        // Параллельные запросы
        const [requests, services, categories, users, reviews, finance, trends] = await Promise.all([
            fetch('requests.php?action=stats' + query).then(r => r.json()),
            fetch('services.php?action=stats' + query).then(r => r.json()),
            fetch('categories.php?action=stats' + query).then(r => r.json()),
            fetch('users.php?action=stats' + query).then(r => r.json()),
            fetch('reviews.php?action=stats' + query).then(r => r.json()),
            fetch('finance.php?action=stats' + query).then(r => r.json()),
            fetch('trends.php?action=stats' + query).then(r => r.json()),
        ]);
        // === ДОБАВЛЯЕМ base64 графики ===
        // Заявки
        if (window.statusPieChart) requests.pieImg = window.statusPieChart.toBase64Image();
        if (window.requestsLineChart) requests.lineImg = window.requestsLineChart.toBase64Image();
        // Услуги
        if (window.servicesBarChart) services.barImg = window.servicesBarChart.toBase64Image();
        if (window.revenuePieChart) services.pieImg = window.revenuePieChart.toBase64Image();
        // Категории
        if (window.requestsBarChart) categories.barImg = window.requestsBarChart.toBase64Image();
        if (window.servicesPieChart) categories.pieImg = window.servicesPieChart.toBase64Image();
        // Пользователи
        if (window.staffBarChart) users.staffBarImg = window.staffBarChart.toBase64Image();
        if (window.staffRequestsBarChart) users.staffRequestsBarImg = window.staffRequestsBarChart.toBase64Image();
        // Отзывы
        if (window.reviewsLineChart) reviews.lineImg = window.reviewsLineChart.toBase64Image();
        // Финансы
        if (window.revenueLineChart) finance.lineImg = window.revenueLineChart.toBase64Image();
        if (window.categoryPieChart) finance.pieImg = window.categoryPieChart.toBase64Image();
        // Тренды
        if (window.requestsLineChart) trends.lineImg = window.requestsLineChart.toBase64Image();
        if (window.seasonBarChart) trends.barImg = window.seasonBarChart.toBase64Image();
        // Собираем всё в один объект
        const data = {
            date_from,
            date_to,
            requests,
            services,
            categories,
            users,
            reviews,
            finance,
            trends
        };
        fetch('/admin/pdf/export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => {
            if (!res.ok) throw new Error('Ошибка генерации PDF');
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'full_analytics_report.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            statusDiv.textContent = 'PDF-отчёт успешно сформирован.';
        })
        .catch(err => {
            statusDiv.textContent = 'Ошибка при генерации PDF: ' + err.message;
        });
    } catch (err) {
        statusDiv.textContent = 'Ошибка при сборе данных: ' + err.message;
    }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 