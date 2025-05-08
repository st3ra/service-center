<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/handlers/form_handler.php';

if (!isset($_GET['service_id'])) {
    die('Не указан ID услуги');
}

$service_id = (int)$_GET['service_id'];
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die('Услуга не найдена');
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

$result = handle_form_submission($pdo, $service_id, $user);
$errors = $result['errors'];
$success = $result['success'];
$form_data = $result['form_data'];
?>

<h1>Запись на услугу: <?php echo htmlspecialchars($service['name']); ?></h1>

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

<form method="post" enctype="multipart/form-data">
    <?php if ($user): ?>
        <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <?php else: ?>
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
    <?php endif; ?>
    <div class="mb-3">
        <label for="description" class="form-label">Описание проблемы</label>
        <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" required><?php echo isset($form_data['description']) ? htmlspecialchars($form_data['description']) : ''; ?></textarea>
        <?php if (isset($errors['description'])): ?>
            <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="file" class="form-label">Прикрепить файл (jpg, png, pdf, до 5 МБ)</label>
        <input type="file" class="form-control <?php echo isset($errors['file']) ? 'is-invalid' : ''; ?>" id="file" name="file">
        <?php if (isset($errors['file'])): ?>
            <div class="invalid-feedback"><?php echo $errors['file']; ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Отправить заявку</button>
</form>

<?php
require_once 'includes/footer.php';
?>