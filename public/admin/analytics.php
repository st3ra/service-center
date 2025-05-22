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
    <style>
        .analytics-card {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: box-shadow 0.2s;
        }
        .analytics-card:hover {
            box-shadow: 0 0 16px #b3b3b3;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Аналитика</h1>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="analytics/requests.php" class="text-decoration-none">
                <div class="card analytics-card">
                    <div class="card-body text-center">
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
