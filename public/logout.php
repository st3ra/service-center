<?php
require_once 'includes/header.php';
require_once 'includes/logout_handler.php';

$success = handle_logout();
?>

<h1>Выход</h1>
<div class="alert alert-success"><?php echo $success; ?></div>

<?php
require_once 'includes/footer.php';
?>