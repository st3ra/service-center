<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../validation.php';

function handle_form_submission($pdo, $service_id, $user) {
    $errors = [];
    $success = '';
    $form_data = [];
    $request_id = null;

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    error_log('Form handler called: method=' . $_SERVER['REQUEST_METHOD'] . ', is_ajax=' . ($is_ajax ? 'true' : 'false'));
    error_log('FILES: ' . json_encode($_FILES));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $errors['general'] = 'Метод не POST';
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'form_data' => $form_data, 'request_id' => $request_id, 'is_authenticated' => $user ? true : false]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'form_data' => $form_data, 'request_id' => $request_id, 'is_authenticated' => $user ? true : false];
    }

    $description = trim($_POST['description'] ?? '');
    $file_paths = [];

    $errors = array_merge($errors, validate_form(['description' => $description]));

    if (!empty($_FILES['files']['name'][0])) {
        $upload_dir = __DIR__ . '/../../uploads/';
        error_log('Upload dir: ' . $upload_dir);

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $errors['files'] = 'Не удалось создать папку uploads/';
                error_log('Failed to create upload dir: ' . $upload_dir);
            }
        }

        if (!is_writable($upload_dir)) {
            $errors['files'] = 'Нет прав на запись в папку public/uploads/';
            error_log('Upload dir not writable: ' . $upload_dir);
        }

        if (empty($errors)) {
            $allowed_ext = ['jpg', 'png', 'pdf'];
            $file_count = count($_FILES['files']['name']);

            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['files']['tmp_name'][$i];
                    $file_name = $_FILES['files']['name'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $file_size = $_FILES['files']['size'][$i];

                    if (!in_array($file_ext, $allowed_ext)) {
                        $errors['files'][] = "Файл '$file_name': Неверный формат (допустимы jpg, png, pdf)";
                    } elseif ($file_size > 5 * 1024 * 1024) {
                        $errors['files'][] = "Файл '$file_name': Слишком большой (макс. 5 МБ)";
                    } else {
                        $upload_path = $upload_dir . uniqid() . '.' . $file_ext;
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $file_paths[] = 'uploads/' . basename($upload_path);
                            error_log('File uploaded successfully: ' . end($file_paths));
                        } else {
                            $errors['files'][] = "Файл '$file_name': Ошибка при загрузке";
                            error_log('Failed to move uploaded file to: ' . $upload_path);
                        }
                    }
                } elseif ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $error_code = $_FILES['files']['error'][$i];
                    $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                    $errors['files'][] = "Файл '$file_name': Ошибка загрузки, код $error_code";
                    error_log("File upload error for '$file_name': code $error_code");
                }
            }
        }
    }

    if ($user) {
        $name = $user['name'];
        $phone = $user['phone'];
        $email = $user['email'];
        $form_data = ['description' => $description];
    } else {
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

        $form_data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'description' => $description
        ];
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($user) {
                $stmt = $pdo->prepare('INSERT INTO requests (user_id, name, phone, email, service_id, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$user['id'], $name, $phone, $email, $service_id, $description, 'new']);
            } else {
                $stmt = $pdo->prepare('INSERT INTO requests (name, phone, email, service_id, description, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $phone, $email, $service_id, $description, 'new']);
            }
            $request_id = $pdo->lastInsertId();

            foreach ($file_paths as $file_path) {
                $stmt = $pdo->prepare('INSERT INTO request_files (request_id, file_path) VALUES (?, ?)');
                $stmt->execute([$request_id, $file_path]);
            }

            $pdo->commit();
            $success = 'Заявка успешно отправлена!';
            
            // Сохраняем ID заявки в сессию для неавторизованных пользователей
            if (!$user && $request_id) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['guest_request_id'] = $request_id;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            error_log('Database error: ' . $e->getMessage());
        }
    } else {
        error_log('Validation errors: ' . json_encode($errors));
    }

    if ($is_ajax) {
        echo json_encode([
            'errors' => $errors,
            'success' => $success,
            'form_data' => $form_data,
            'request_id' => $request_id,
            'is_authenticated' => $user ? true : false
        ]);
        exit;
    }

    return [
        'errors' => $errors,
        'success' => $success,
        'form_data' => $form_data,
        'request_id' => $request_id,
        'is_authenticated' => $user ? true : false
    ];
}