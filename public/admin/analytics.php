<?php
// Страница аналитики
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика | Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .analytics-card {
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
            border-radius: 1rem;
        }
        .analytics-card:hover {
            box-shadow: 0 0 20px #b3b3b3;
            transform: translateY(-4px) scale(1.03);
        }
        .analytics-icon {
            font-size: 2.2rem;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .analytics-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .analytics-card .card-text {
            font-size: 0.97rem;
            color: #555;
        }
        .back-btn {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="/admin/index.php" class="btn btn-outline-secondary back-btn"><i class="bi bi-arrow-left"></i> В меню админ-панели</a>
        <h1 class="mb-0">Аналитика</h1>
        <div></div>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="analytics/requests.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-bar-chart-line"></i></div>
                        <h5 class="card-title">Статистика по заявкам</h5>
                        <p class="card-text">Общее количество, статусы, динамика, топ-дни</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/services.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-gear"></i></div>
                        <h5 class="card-title">Аналитика по услугам</h5>
                        <p class="card-text">Популярные услуги, выручка, услуги без заявок</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/categories.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-tags"></i></div>
                        <h5 class="card-title">Аналитика по категориям</h5>
                        <p class="card-text">Количество услуг и заявок по категориям</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/users.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-people"></i></div>
                        <h5 class="card-title">Активность пользователей</h5>
                        <p class="card-text">Топ-клиенты, активность сотрудников, обработка заявок</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/reviews.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-chat-dots"></i></div>
                        <h5 class="card-title">Аналитика отзывов</h5>
                        <p class="card-text">Количество, динамика, последние отзывы</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/finance.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-cash-coin"></i></div>
                        <h5 class="card-title">Финансовая аналитика</h5>
                        <p class="card-text">Выручка, средний чек, динамика по месяцам</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/trends.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-calendar3"></i></div>
                        <h5 class="card-title">Сезонность и тренды</h5>
                        <p class="card-text">Популярные месяцы и услуги по сезонам</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="analytics/export.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
                        <div class="analytics-icon"><i class="bi bi-download"></i></div>
                        <h5 class="card-title">Экспорт данных</h5>
                        <p class="card-text">Выгрузка заявок и отзывов в CSV, PDF-отчёты</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
