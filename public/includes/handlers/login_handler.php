<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../validation.php';

function handle_login($pdo) {
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
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $success = 'Вход успешен!';
                $nav_html = '
                    <li class="nav-item">
                        <a class="nav-link" href="/profile.php">Профиль</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-action="logout">Выйти</a>
                    </li>';
            } else {
                $errors['general'] = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    }

    $form_data = ['email' => $email];

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