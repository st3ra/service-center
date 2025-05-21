<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../validation.php';

function handle_admin_login($pdo) {
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $errors['general'] = 'Метод не POST';
        return ['errors' => $errors, 'success' => false];
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = array_merge($errors, validate_form([
        'email' => $email,
        'password' => $password
    ]));

    if (!isset($errors['email']) && $email) {
        $email_error = validate_email($email);
        if ($email_error) $errors['email'] = $email_error;
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (in_array($user['role'], ['admin', 'worker', 'editor'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    return ['success' => true];
                } else {
                    $errors['general'] = 'У вас нет прав для доступа к админ-панели';
                }
            } else {
                $errors['general'] = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    }

    return ['errors' => $errors, 'success' => false];
}