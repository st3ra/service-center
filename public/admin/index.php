<?php
require_once 'includes/auth_check.php';
require_once '../includes/db.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-card {
            min-height: 170px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
            border: none;
        }
        .admin-card:hover {
            box-shadow: 0 0 16px #b3b3b3;
            transform: translateY(-4px) scale(1.03);
        }
        .admin-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 12px;
        }
        .admin-link {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4 text-center">Добро пожаловать в админ-панель!</h1>
        <p class="text-center mb-5 text-muted">Выберите раздел для управления сервисным центром</p>
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/requests.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-list-check"></i></div>
                            <h5 class="card-title">Заявки</h5>
                            <p class="card-text small text-muted">Просмотр и обработка заявок</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/users.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-people"></i></div>
                            <h5 class="card-title">Пользователи</h5>
                            <p class="card-text small text-muted">Клиенты и сотрудники</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/categories.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-tags"></i></div>
                            <h5 class="card-title">Категории</h5>
                            <p class="card-text small text-muted">Управление категориями услуг</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/analytics.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-bar-chart"></i></div>
                            <h5 class="card-title">Аналитика</h5>
                            <p class="card-text small text-muted">Статистика и отчёты</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/reviews.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-chat-dots"></i></div>
                            <h5 class="card-title">Отзывы</h5>
                            <p class="card-text small text-muted">Модерация и редактирование отзывов</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="/admin/logout.php" class="admin-link">
                    <div class="card admin-card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="admin-icon"><i class="bi bi-box-arrow-right"></i></div>
                            <h5 class="card-title">Выйти</h5>
                            <p class="card-text small text-muted">Завершить сессию</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>