<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../validation.php';

function handle_profile_edit($pdo) {
    $errors = [];
    $success = '';
    $user_data = [];

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'user_data' => $user_data]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'user_data' => $user_data];
    }

    if (!isset($_SESSION['user_id'])) {
        $errors['general'] = 'Необходимо войти';
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'user_data' => $user_data]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'user_data' => $user_data];
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $errors = array_merge($errors, validate_form([
        'name' => $name,
        'phone' => $phone,
        'email' => $email
    ]));

    if (!isset($errors['email']) && $email) {
        $email_error = validate_email($email);
        if ($email_error) $errors['email'] = $email_error;
    }
    if (!isset($errors['phone']) && $phone) {
        $phone_error = validate_phone($phone);
        if ($phone_error) $errors['phone'] = $phone_error;
    }

    if (empty($errors)) {
        try {
            // Проверяем, не занят ли email другим пользователем
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Этот email уже зарегистрирован';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?');
                $stmt->execute([$name, $phone, $email, $_SESSION['user_id']]);
                $success = 'Данные успешно обновлены!';
                $user_data = ['name' => $name, 'phone' => $phone, 'email' => $email];
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    } else {
        $user_data = ['name' => $name, 'phone' => $phone, 'email' => $email];
    }

    if ($is_ajax) {
        echo json_encode([
            'errors' => $errors,
            'success' => $success,
            'user_data' => $user_data
        ]);
        exit;
    }

    return [
        'errors' => $errors,
        'success' => $success,
        'user_data' => $user_data
    ];
}