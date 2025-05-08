<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сервисный центр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="/">Сервисный центр</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="/services.php">Услуги</a></li>
                        <li class="nav-item"><a class="nav-link" href="/contacts.php">Контакты</a></li>
                        <li class="nav-item"><a class="nav-link" href="/about.php">О нас</a></li>
                        <li class="nav-item"><a class="nav-link" href="/reviews.php">Отзывы</a></li>
                    </ul>
                    <ul class="navbar-nav" id="auth-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item"><a class="nav-link" href="/profile.php">Профиль</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-action="logout">Выйти</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/login.php">Вход</a></li>
                            <li class="nav-item"><a class="nav-link" href="/register.php">Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container mt-4">
        <div id="notification" class="alert" style="display:none;"></div>
    <!-- Остальной контент -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/auth.js"></script>
</body>
</html>