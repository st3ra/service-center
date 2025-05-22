<?php
require_once '../../includes/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');

    // --- Фильтры ---
    $whereRequests = ["u.role = 'client'"];
    $paramsRequests = [];
    $whereComments = ["u.role = 'worker'"];
    $paramsComments = [];
    if (!empty($_GET['date_from'])) {
        $whereRequests[] = 'r.created_at >= ?';
        $paramsRequests[] = $_GET['date_from'] . ' 00:00:00';
        $whereComments[] = 'c.created_at >= ?';
        $paramsComments[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $whereRequests[] = 'r.created_at <= ?';
        $paramsRequests[] = $_GET['date_to'] . ' 23:59:59';
        $whereComments[] = 'c.created_at <= ?';
        $paramsComments[] = $_GET['date_to'] . ' 23:59:59';
    }
    $whereRequestsSql = $whereRequests ? ('WHERE ' . implode(' AND ', $whereRequests)) : '';
    $whereCommentsSql = $whereComments ? ('WHERE ' . implode(' AND ', $whereComments)) : '';

    // --- 1. Количество уникальных клиентов ---
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'client'";
    $uniqueClients = $pdo->query($sql)->fetchColumn();

    // --- 2. Топ-5 клиентов по количеству заявок и сумме (только client, только завершённые заявки) ---
    $sql = "SELECT u.name, u.email, COUNT(r.id) as requests_count, COALESCE(SUM(s.price),0) as total_sum
            FROM users u
            LEFT JOIN requests r ON r.user_id = u.id AND r.status = 'completed'
            LEFT JOIN services s ON r.service_id = s.id
            $whereRequestsSql
            GROUP BY u.id, u.name, u.email
            ORDER BY requests_count DESC, total_sum DESC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsRequests);
    $topClients = $stmt->fetchAll();

    // --- 3. Активность сотрудников по комментариям (только worker) ---
    $sql = "SELECT u.id, u.name, u.role, COUNT(c.id) as comments_count
            FROM users u
            LEFT JOIN request_comments c ON c.user_id = u.id
            $whereCommentsSql
            GROUP BY u.id, u.name, u.role
            ORDER BY comments_count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsComments);
    $staffComments = $stmt->fetchAll();

    // --- 4. Количество заявок, обработанных каждым worker (по комментариям) ---
    $sql = "SELECT u.id, u.name, COUNT(DISTINCT c.request_id) as requests_handled
            FROM users u
            LEFT JOIN request_comments c ON c.user_id = u.id
            $whereCommentsSql
            GROUP BY u.id, u.name
            ORDER BY requests_handled DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsComments);
    $staffRequests = $stmt->fetchAll();

    // --- 5. Топ-5 сотрудников по заявкам или комментариям ---
    $sort = isset($_GET['sort']) && $_GET['sort'] === 'requests' ? 'requests' : 'comments';
    $topStaff = [];
    if ($sort === 'requests') {
        // По заявкам
        $sql = "SELECT u.name, COUNT(DISTINCT c.request_id) as requests_handled, COUNT(c.id) as comments_count
                FROM users u
                LEFT JOIN request_comments c ON c.user_id = u.id
                $whereCommentsSql
                GROUP BY u.id, u.name
                ORDER BY requests_handled DESC, comments_count DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsComments);
        $topStaff = $stmt->fetchAll();
    } else {
        // По комментариям
        $sql = "SELECT u.name, COUNT(DISTINCT c.request_id) as requests_handled, COUNT(c.id) as comments_count
                FROM users u
                LEFT JOIN request_comments c ON c.user_id = u.id
                $whereCommentsSql
                GROUP BY u.id, u.name
                ORDER BY comments_count DESC, requests_handled DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsComments);
        $topStaff = $stmt->fetchAll();
    }

    echo json_encode([
        'uniqueClients' => (int)$uniqueClients,
        'topClients' => $topClients,
        'staffComments' => $staffComments,
        'staffRequests' => $staffRequests,
        'topStaff' => $topStaff,
        'topStaffSort' => $sort,
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
    <title>Аналитика по пользователям | Админ-панель</title>
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
        <h1 class="mb-0">Активность пользователей</h1>
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
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Уникальных клиентов</h6>
                    <div class="display-6" id="unique-clients">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Топ-5 клиентов</h6>
                    <ul class="list-unstyled mb-0 small" id="top-clients-list" style="text-align:left"></ul>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Активность сотрудников</h6>
                    <div class="display-6" id="staff-comments">...</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body text-center">
                    <h6 class="card-title">Заявок обработано сотрудниками</h6>
                    <div class="display-6" id="staff-requests">...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Количество комментариев по сотрудникам</h6>
                    <div class="chart-container">
                        <canvas id="staffBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Количество заявок по сотрудникам</h6>
                    <div class="chart-container">
                        <canvas id="staffRequestsBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Топ-5 клиентов</h6>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Клиент</th>
                                    <th>Заявок</th>
                                    <th>Сумма заявок</th>
                                </tr>
                            </thead>
                            <tbody id="top-clients-table">
                                <tr><td colspan="3" class="text-center">Загрузка...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div id="top-staff-block"><!-- Топ-5 сотрудников (таблица с сортировкой) --></div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-success" id="download-pdf">Скачать PDF-отчёт</button>
    </div>
</div>
<script src="/assets/js/admin/analytics/users.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 