<?php
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

$stmt = $pdo->query('SELECT r.*, u.name AS user_name, rq.id AS request_id, rq.description AS request_desc FROM reviews r JOIN users u ON r.user_id = u.id JOIN requests rq ON r.request_id = rq.id ORDER BY r.created_at DESC');
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отзывы — Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Отзывы</h1>
        <a href="/admin/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
    <div id="reviews-table-container">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Заявка</th>
                    <th scope="col">Пользователь</th>
                    <th scope="col">Текст</th>
                    <th scope="col">Дата</th>
                    <th scope="col" class="text-center">Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Нет отзывов</td></tr>
            <?php else: foreach ($reviews as $review): ?>
                <tr>
                    <td><?= $review['id'] ?></td>
                    <td><a href="/admin/request.php?id=<?= $review['request_id'] ?>" target="_blank">#<?= $review['request_id'] ?></a></td>
                    <td><?= htmlspecialchars($review['user_name']) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($review['text'], 0, 60, '…')) ?></td>
                    <td><?= htmlspecialchars($review['created_at']) ?></td>
                    <td class="text-center">
                        <a href="review.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Подробнее"><i class="bi bi-eye"></i></a>
                        <a href="review.php?id=<?= $review['id'] ?>&delete=1" class="btn btn-sm btn-outline-danger" title="Удалить" onclick="return confirm('Удалить отзыв?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
