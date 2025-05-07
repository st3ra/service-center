<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die('Необходимо войти');
}

$stmt = $pdo->prepare('SELECT * FROM requests WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>
<h1>Мои заявки</h1>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Услуга</th>
            <th>Статус</th>
            <th>Дата</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo $request['id']; ?></td>
                <td><?php echo $request['service_id']; ?></td>
                <td><?php echo $request['status']; ?></td>
                <td><?php echo $request['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
require_once 'includes/footer.php';
?>