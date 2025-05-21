<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/handlers/admin_login_handler.php';

if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'worker', 'editor'])) {
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $result = handle_admin_login($pdo);
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else if ($result['success']) {
        header('Location: /admin/index.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Вход в админ-панель</h1>
        <div id="notification" class="alert" style="display: none;"></div>
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        <form method="POST" id="admin-login-form">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Войти</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin/utils.js"></script>
    <script src="/assets/js/admin/login.js"></script>
</body>
</html>