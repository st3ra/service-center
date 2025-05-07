<?php
require_once 'db.php';
require_once 'validation.php';

function handle_login($pdo) {
    $errors = [];
    $success = '';
    $form_data = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['errors' => $errors, 'success' => $success, 'form_data' => $form_data];
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Валидация обязательных полей
    $errors = array_merge($errors, validate_form([
        'email' => $email,
        'password' => $password
    ]));

    // Валидация формата email
    if (!isset($errors['email']) && $email) {
        $email_error = validate_email($email);
        if ($email_error) $errors['email'] = $email_error;
    }

    // Если ошибок валидации нет, проверяем пользователя
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $success = 'Вход успешен! <a href="index.php">Перейти на главную</a>.';
            } else {
                $errors['general'] = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    }

    $form_data = ['email' => $email];

    return [
        'errors' => $errors,
        'success' => $success,
        'form_data' => $form_data
    ];
}