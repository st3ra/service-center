<?php
require_once 'includes/register_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    session_start();
    handle_registration($pdo);
    exit;
}

require_once 'includes/header.php';

$result = handle_registration($pdo);
$errors = $result['errors'];
$success = $result['success'];
$form_data = $result['form_data'] ?? [];
?>

<h1>Регистрация</h1>

<div id="notification" class="alert" style="display:none;"></div>

<form id="register-form">
    <div class="mb-3">
        <label for="name" class="form-label">ФИО</label>
        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo isset($form_data['name']) ? htmlspecialchars($form_data['name']) : ''; ?>" required>
        <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Телефон</label>
        <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>" required>
        <?php if (isset($errors['phone'])): ?>
            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
        <?php endif; ?>
    </div>
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
    <div class="mb-3">
        <label for="password_confirm" class="form-label">Подтверждение пароля</label>
        <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" id="password_confirm" name="password_confirm" required>
        <?php if (isset($errors['password_confirm'])): ?>
            <div class="invalid-feedback"><?php echo $errors['password_confirm']; ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
</form>

<?php
require_once 'includes/footer.php';
?>