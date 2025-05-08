<?php
function handle_logout() {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    $_SESSION = [];
    session_destroy();
    $success = 'Вы успешно вышли из аккаунта.';
    $nav_html = '
        <li class="nav-item">
            <a class="nav-link" href="/login.php">Вход</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/register.php">Регистрация</a>
        </li>';

    if ($is_ajax) {
        echo json_encode([
            'success' => $success,
            'nav_html' => $nav_html
        ]);
        exit;
    }

    return $success;
}