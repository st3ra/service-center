<?php
require_once 'includes/login_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    session_start();
    handle_login($pdo);
    exit;
}

require_once 'includes/header.php';

$result = handle_login($pdo);
$errors = $result['errors'];
$success = $result['success'];
$form_data = $result['form_data'] ?? [];
?>

<h1>Вход</h1>

<div id="notification" class="alert" style="display:none;"></div>

<form id="login-form">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Войти</button>
</form>

<?php
require_once 'includes/footer.php';
?>