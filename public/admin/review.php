<?php
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    die('Не указан ID отзыва');
}
$review_id = (int)$_GET['id'];

// Удаление
if (isset($_GET['delete']) && $_GET['delete'] == 1) {
    $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
    $stmt->execute([$review_id]);
    header('Location: reviews.php?deleted=1');
    exit;
}

// Получаем отзыв
$stmt = $pdo->prepare('SELECT r.*, u.name AS user_name, rq.id AS request_id, rq.description AS request_desc FROM reviews r JOIN users u ON r.user_id = u.id JOIN requests rq ON r.request_id = rq.id WHERE r.id = ?');
$stmt->execute([$review_id]);
$review = $stmt->fetch();
if (!$review) die('Отзыв не найден');

// Редактирование
$success = '';
$error = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
    $text = trim($_POST['text']);
    if (mb_strlen($text) > 1000) {
        $error = 'Текст слишком длинный (максимум 1000 символов)';
    } else {
        $stmt = $pdo->prepare('UPDATE reviews SET text = ? WHERE id = ?');
        $stmt->execute([$text, $review_id]);
        $success = 'Отзыв обновлён';
        $review['text'] = $text;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отзыв #<?= $review['id'] ?> — Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Отзыв #<?= $review['id'] ?></h1>
        <a href="reviews.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <strong>Заявка:</strong> <a href="/admin/request.php?id=<?= $review['request_id'] ?>" target="_blank">#<?= $review['request_id'] ?></a>
            </div>
            <div class="mb-3">
                <strong>Пользователь:</strong> <?= htmlspecialchars($review['user_name']) ?>
            </div>
            <div class="mb-3">
                <strong>Дата:</strong> <?= htmlspecialchars($review['created_at']) ?>
            </div>
            <div class="mb-3 d-flex align-items-start" id="review-text-block">
                <strong class="me-2">Текст отзыва:</strong>
                <div class="flex-grow-1">
                    <p id="review-text" class="mb-0"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                    <form method="post" id="review-edit-form" style="display:none;">
                        <textarea name="text" id="text" class="form-control mb-2" rows="5" maxlength="1000" required><?= htmlspecialchars($review['text']) ?></textarea>
                        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="button" class="btn btn-secondary ms-2" id="cancel-edit-btn">Отмена</button>
                    </form>
                </div>
            </div>
            <div id="review-actions">
                <button class="btn btn-outline-primary btn-sm" id="edit-review-btn"><i class="bi bi-pencil"></i> Редактировать</button>
                <a href="review.php?id=<?= $review['id'] ?>&delete=1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('Удалить отзыв?')"><i class="bi bi-trash"></i> Удалить</a>
            </div>
            <div class="mt-4">
                <h5>Описание заявки</h5>
                <p><?= htmlspecialchars($review['request_desc']) ?></p>
            </div>
        </div>
    </div>
</div>
<script>
$(function() {
    $('#edit-review-btn').on('click', function() {
        $('#review-text').hide();
        $('#review-edit-form').show();
        $('#review-actions').hide();
    });
    $('#cancel-edit-btn').on('click', function() {
        $('#review-edit-form').hide();
        $('#review-text').show();
        $('#review-actions').show();
    });
});
</script>
</body>
</html> 