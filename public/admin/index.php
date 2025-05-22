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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Добро пожаловать в админ-панель!</h1>
        <p>Здесь вы можете управлять заявками и пользователями.</p>
        <nav>
            <ul class="nav">
                <li class="nav-item"><a class="nav-link" href="/admin/requests.php">Заявки</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/users.php">Пользователи</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/categories.php">Категории</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/analytics.php">Аналитика</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/logout.php">Выйти</a></li>
            </ul>
        </nav>
    </div>
</body>
</html>