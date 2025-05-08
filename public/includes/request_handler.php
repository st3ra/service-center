<?php
require_once 'db.php';
require_once 'validation.php';

function handle_request_edit($pdo, $request_id) {
    $errors = [];
    $success = '';
    $request_data = [];

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'request_data' => $request_data]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'request_data' => $request_data];
    }

    if (!isset($_SESSION['user_id'])) {
        $errors['general'] = 'Необходимо войти';
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'request_data' => $request_data]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'request_data' => $request_data];
    }

    // Проверяем, что заявка принадлежит пользователю
    $stmt = $pdo->prepare('SELECT id FROM requests WHERE id = ? AND user_id = ?');
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        $errors['general'] = 'Заявка не найдена или доступ запрещён';
        if ($is_ajax) {
            echo json_encode(['errors' => $errors, 'success' => $success, 'request_data' => $request_data]);
            exit;
        }
        return ['errors' => $errors, 'success' => $success, 'request_data' => $request_data];
    }

    $description = trim($_POST['description'] ?? '');

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE requests SET description = ? WHERE id = ?');
            $stmt->execute([$description, $request_id]);
            $success = 'Заявка успешно обновлена!';
            $request_data = ['description' => $description];
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка: ' . $e->getMessage();
        }
    } else {
        $request_data = ['description' => $description];
    }

    if ($is_ajax) {
        echo json_encode([
            'errors' => $errors,
            'success' => $success,
            'request_data' => $request_data
        ]);
        exit;
    }

    return [
        'errors' => $errors,
        'success' => $success,
        'request_data' => $request_data
    ];
}