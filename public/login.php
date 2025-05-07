<?php
require_once 'includes/header.php';
require_once 'includes/login_handler.php';

$result = handle_login($pdo);
$errors = $result['errors'];
$success = $result['success'];
$form_data = $result['form_data'];
?>

<h1>Вход</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post">
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