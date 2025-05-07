<?php
function handle_logout() {
    $_SESSION = [];
    session_destroy();
    return 'Вы успешно вышли из аккаунта. <a href="index.php">Вернуться на главную</a>.';
}