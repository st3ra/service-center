<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';

function handle_registration($pdo) {
    $errors = [];
    $success = '';
    $form_data = [];
    $nav_html = '';

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'nav_html' => $nav_html]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'form_data' => $form_data];
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = 'client';

    $errors = array_merge($errors, validate_form([
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'password' => $password,
        'password_confirm' => $password_confirm
    ]));

    if (!isset($errors['email']) && $email) {
        $email_error = validate_email($email);
        if ($email_error) $errors['email'] = $email_error;
    }
    if (!isset($errors['phone']) && $phone) {
        $phone_error = validate_phone($phone);
        if ($phone_error) $errors['phone'] = $phone_error;
    }
    if (!isset($errors['password']) && $password) {
        $password_error = validate_password($password);
        if ($password_error) $errors['password'] = $password_error;
    }
    if (!isset($errors['password']) && !isset($errors['password_confirm']) && $password && $password_confirm) {
        $password_confirm_error = validate_password_confirm($password, $password_confirm);
        if ($password_confirm_error) $errors['password_confirm'] = $password_confirm_error;
    }

    if (empty($errors)) {
        try {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $phone, $email, $password_hashed, $role]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = $role;
            $success = 'Регистрация успешна!';
            $nav_html = '
                <li class="nav-item">
                    <a class="nav-link" href="/profile.php">Профиль</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-action="logout">Выйти</a>
                </li>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['email'] = 'Email уже зарегистрирован';
            } else {
                $errors['general'] = 'Ошибка: ' . $e->getMessage();
            }
        }
    }

    $form_data = [
        'name' => $name,
        'phone' => $phone,
        'email' => $email
    ];

    if ($is_ajax) {
        echo json_encode([
            'errors' => $errors,
            'success' => $success,
            'nav_html' => $nav_html
        ]);
        exit;
    }

    return [
        'errors' => $errors,
        'success' => $success,
        'form_data' => $form_data
    ];
}