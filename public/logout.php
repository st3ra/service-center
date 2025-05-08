<?php
require_once 'includes/logout_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    session_start();
    handle_logout();
    exit;
}

require_once 'includes/header.php';

$success = handle_logout();
?>

<h1>Выход</h1>
<div class="alert alert-success"><?php echo $success; ?></div>

<?php
require_once 'includes/footer.php';
?>