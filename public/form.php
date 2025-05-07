<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_GET['service_id'])) {
    die('Не указан ID услуги');
}

$service_id = $_GET['service_id'];
$service = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$service->execute([$service_id]);
$service = $service->fetch();

if (!$service) {
    die('Услуга не найдена');
}

if (isset($_SESSION['user_id'])) {
    $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $user->execute([$_SESSION['user_id']]);
    $user = $user->fetch();
} else {
    $user = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'];
    $file_path = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = $_FILES['file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'png', 'pdf'];

        if (in_array($file_ext, $allowed_ext) && $_FILES['file']['size'] <= 5 * 1024 * 1024) {
            $file_path = 'uploads/' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $file_path);
        } else {
            echo 'Неверный формат или размер файла';
        }
    }

    if ($user) {
        $stmt = $pdo->prepare('INSERT INTO requests (user_id, service_id, description, file_path, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $service_id, $description, $file_path, 'new']);
    } else {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $stmt = $pdo->prepare('INSERT INTO requests (name, phone, email, address, service_id, description, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $phone, $email, $address, $service_id, $description, $file_path, 'new']);
    }

    echo 'Заявка успешно отправлена';
}
?>
<h1>Запись на услугу: <?php echo $service['name']; ?></h1>
<form method="post" enctype="multipart/form-data">
    <?php if ($user): ?>
        <p>ФИО: <?php echo $user['name']; ?></p>
        <p>Телефон: <?php echo $user['phone']; ?></p>
        <p>Email: <?php echo $user['email']; ?></p>
        <p>Адрес: <?php echo $user['address']; ?></p>
    <?php else: ?>
        <div class="mb-3">
            <label for="name" class="form-label">ФИО</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Адрес</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
    <?php endif; ?>
    <div class="mb-3">
        <label for="description" class="form-label">Описание проблемы</label>
        <textarea class="form-control" id="description" name="description" required></textarea>
    </div>
    <div class="mb-3">
        <label for="file" class="form-label">Прикрепить файл (jpg, png, pdf, до 5 МБ)</label>
        <input type="file" class="form-control" id="file" name="file">
    </div>
    <button type="submit" class="btn btn-primary">Отправить заявку</button>
</form>
<?php
require_once 'includes/footer.php';
?>