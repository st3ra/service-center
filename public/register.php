<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'client';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Неверный формат email';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        echo 'Неверный формат телефона';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, address, password, role) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $phone, $email, $address, $password, $role]);
            echo 'Регистрация успешна';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo 'Email уже зарегистрирован';
            } else {
                echo 'Ошибка: ' . $e->getMessage();
            }
        }
    }
}
?>
<h1>Регистрация</h1>
<form method="post">
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
    <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
</form>
<?php
require_once 'includes/footer.php';
?>