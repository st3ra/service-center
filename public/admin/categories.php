<?php
require_once 'includes/auth_check.php';
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_editor = isset($_SESSION['role']) && $_SESSION['role'] === 'editor';
$is_worker = isset($_SESSION['role']) && $_SESSION['role'] === 'worker';

// Обработка добавления категории
$add_error = '';
$add_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!($is_admin || $is_editor)) {
        $add_error = 'Недостаточно прав для добавления категории';
    } else {
        $add_name = trim($_POST['add_category_name'] ?? '');
        if ($add_name === '') {
            $add_error = 'Название не может быть пустым';
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            if ($stmt->execute([$add_name])) {
                $add_success = 'Категория успешно добавлена';
            } else {
                $add_error = 'Ошибка при добавлении категории';
            }
        }
    }
}

// Обработка редактирования категории
$edit_error = '';
$edit_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'])) {
    if (!($is_admin || $is_editor)) {
        $edit_error = 'Недостаточно прав для редактирования категории';
    } else {
        $edit_id = (int)$_POST['edit_category_id'];
        $edit_name = trim($_POST['edit_category_name'] ?? '');
        if ($edit_name === '') {
            $edit_error = 'Название не может быть пустым';
        } else {
            $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
            if ($stmt->execute([$edit_name, $edit_id])) {
                $edit_success = 'Категория успешно обновлена';
            } else {
                $edit_error = 'Ошибка при обновлении категории';
            }
        }
    }
}

// Обработка удаления категории
$delete_error = '';
$delete_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    if (!($is_admin || $is_editor)) {
        $delete_error = 'Недостаточно прав для удаления категории';
    } else {
        $delete_id = (int)$_POST['delete_category_id'];
        // Проверяем, есть ли связанные услуги
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM services WHERE category_id = ?');
        $stmt->execute([$delete_id]);
        $service_count = $stmt->fetchColumn();
        if ($service_count > 0) {
            $delete_error = 'Нельзя удалить категорию, к которой привязаны услуги!';
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            if ($stmt->execute([$delete_id])) {
                $delete_success = 'Категория успешно удалена';
            } else {
                $delete_error = 'Ошибка при удалении категории';
            }
        }
    }
}

// Получение списка категорий с подсчётом количества услуг
$stmt = $pdo->query('
    SELECT c.id, c.name, COUNT(s.id) AS service_count
    FROM categories c
    LEFT JOIN services s ON s.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AJAX-поиск по названию категории
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = 'WHERE c.name LIKE :search';
        $params['search'] = '%' . $search . '%';
    }
    $sql = '
        SELECT c.id, c.name, COUNT(s.id) AS service_count
        FROM categories c
        LEFT JOIN services s ON s.category_id = c.id
        ' . $where . '
        GROUP BY c.id
        ORDER BY c.id ASC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['categories' => $categories]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категории услуг</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Категории услуг</h1>
            <a href="/admin/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
        <div class="mb-3 row g-2 align-items-center">
            <div class="col-auto">
                <input type="text" id="search-categories" class="form-control" placeholder="Поиск по названию...">
            </div>
            <?php if ($is_admin || $is_editor): ?>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus"></i> Добавить категорию
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php foreach ([$add_error, $edit_error, $delete_error] as $err) { if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; } ?>
        <?php foreach ([$add_success, $edit_success, $delete_success] as $succ) { if ($succ): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succ) ?></div>
        <?php endif; } ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Услуг в категории</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="categories-table-body">
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= $cat['service_count'] ?></td>
                        <td>
                            <a href="/admin/services.php?category_id=<?= $cat['id'] ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-list"></i> Услуги
                            </a>
                            <?php if ($is_admin || $is_editor): ?>
                            <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">
                                <i class="bi bi-pencil"></i> Редактировать
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно для добавления категории -->
    <?php if ($is_admin || $is_editor): ?>
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="addCategoryModalLabel">Добавить категорию</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="add_category_name" class="form-label">Название категории</label>
                <input type="text" class="form-control" name="add_category_name" id="add_category_name" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-primary" name="add_category" value="1">Добавить</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Модальное окно для редактирования категории -->
    <?php if ($is_admin || $is_editor): ?>
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="editCategoryModalLabel">Редактировать категорию</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="edit_category_id" id="edit_category_id">
              <div class="mb-3">
                <label for="edit_category_name" class="form-label">Название категории</label>
                <input type="text" class="form-control" name="edit_category_name" id="edit_category_name" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Модальное окно для подтверждения удаления -->
    <?php if ($is_admin || $is_editor): ?>
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteCategoryModalLabel">Удалить категорию</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="delete_category_id" id="delete_category_id">
              <p>Вы действительно хотите удалить категорию <b id="delete_category_name"></b>?</p>
              <p class="text-danger small mb-0">Категорию можно удалить только если к ней не привязаны услуги.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-danger">Удалить</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <script>
        window.isAdminOrEditor = <?= ($is_admin || $is_editor) ? 'true' : 'false' ?>;
    </script>
    <script src="/assets/js/admin/search_categories.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(function() {
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#edit_category_id').val(id);
            $('#edit_category_name').val(name);
            var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        });
        $('.delete-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#delete_category_id').val(id);
            $('#delete_category_name').text(name);
            var modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        });
    });
    </script>
</body>
</html>