<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';

function handle_form_submission($pdo, $service_id, $user) {
    $errors = [];
    $success = '';
    $form_data = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['errors' => $errors, 'success' => $success, 'form_data' => $form_data];
    }

    $description = trim($_POST['description']);
    $file_path = null;

    // Валидация описания
    $errors = array_merge($errors, validate_form(['description' => $description]));

    // Валидация файла
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = $_FILES['file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'png', 'pdf'];

        if (!in_array($file_ext, $allowed_ext)) {
            $errors['file'] = 'Неверный формат файла (допустимы jpg, png, pdf)';
        } elseif ($_FILES['file']['size'] > 5 * 1024 * 1024) {
            $errors['file'] = 'Файл слишком большой (макс. 5 МБ)';
        } else {
            $file_path = 'uploads/' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $file_path);
        }
    }

    // Валидация для незарегистрированных пользователей
    if (!$user) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        $errors = array_merge($errors, validate_form([
            'name' => $name,
            'phone' => $phone,
            'email' => $email
        ]));

        if (!$errors['email']) {
            $email_error = validate_email($email);
            if ($email_error) $errors['email'] = $email_error;
        }
        if (!$errors['phone']) {
            $phone_error = validate_phone($phone);
            if ($phone_error) $errors['phone'] = $phone_error;
        }

        $form_data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'description' => $description
        ];
    } else {
        $form_data = ['description' => $description];
    }

    // Если ошибок нет, сохраняем заявку
    if (empty($errors)) {
        try {
            if ($user) {
                $stmt = $pdo->prepare('INSERT INTO requests (user_id, service_id, description, file_path, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$user['id'], $service_id, $description, $file_path, 'new']);
            } else {
                $stmt = $pdo->prepare('INSERT INTO requests (name, phone, email, service_id, description, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $phone, $email, $service_id, $description, $file_path, 'new']);
            }
            $success = 'Заявка успешно отправлена!';
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    }

    return [
        'errors' => $errors,
        'success' => $success,
        'form_data' => $form_data
    ];
}