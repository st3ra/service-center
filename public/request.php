<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/handlers/request_handler.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Определяем, авторизован ли пользователь или это гость с правом просмотра
$is_guest_view = false;
if (!isset($_SESSION['user_id'])) {
    $allow_guest = false;
    if (isset($_SESSION['guest_request_id']) && isset($_GET['id'])) {
        $guest_id = (int)$_SESSION['guest_request_id'];
        $req_id = (int)$_GET['id'];
        if ($guest_id === $req_id) {
            $allow_guest = true;
            $is_guest_view = true;
        }
    }
    if (!$allow_guest) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['errors' => ['general' => 'Необходимо войти']]);
            exit;
        }
        header('Location: /');
        exit;
    }
}

if (!isset($_GET['id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'ID заявки не указан']]);
        exit;
    }
    die('ID заявки не указан');
}

$request_id = (int)$_GET['id'];

if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'edit_request') {
        $description = trim($_POST['description'] ?? '');
        $files_to_delete = json_decode($_POST['files_to_delete'] ?? '[]', true);
        $file_paths = [];
        $errors = [];

        $errors = array_merge($errors, validate_form(['description' => $description]));

        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (!is_writable($upload_dir)) {
                $errors['files'] = 'Нет прав на запись в папку uploads/';
            } else {
                $allowed_ext = ['jpg', 'png', 'pdf'];
                $file_count = count($_FILES['files']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['files']['tmp_name'][$i];
                        $file_name = $_FILES['files']['name'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $file_size = $_FILES['files']['size'][$i];
                        if (!in_array($file_ext, $allowed_ext)) {
                            $errors['files'][] = "Файл '$file_name': Неверный формат";
                        } elseif ($file_size > 5 * 1024 * 1024) {
                            $errors['files'][] = "Файл '$file_name': Слишком большой";
                        } else {
                            $upload_path = $upload_dir . uniqid() . '.' . $file_ext;
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $file_paths[] = 'uploads/' . basename($upload_path);
                            } else {
                                $errors['files'][] = "Файл '$file_name': Ошибка загрузки";
                            }
                        }
                    } elseif ($_FILES['files']['error'][$i] === UPLOAD_ERR_INI_SIZE) {
                        $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                        $errors['files'][] = "Файл '$file_name': Слишком большой для серверных настроек (макс. 5 МБ)";
                    } elseif ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $file_name = $_FILES['files']['name'][$i] ?? 'неизвестный файл';
                        $errors['files'][] = "Файл '$file_name': Ошибка загрузки, код {$_FILES['files']['error'][$i]}";
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('UPDATE requests SET description = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$description, $request_id, $_SESSION['user_id']]);

                foreach ($file_paths as $file_path) {
                    $stmt = $pdo->prepare('INSERT INTO request_files (request_id, file_path) VALUES (?, ?)');
                    $stmt->execute([$request_id, $file_path]);
                }

                if (!empty($files_to_delete)) {
                    $placeholders = implode(',', array_fill(0, count($files_to_delete), '?'));
                    $stmt = $pdo->prepare("SELECT id, file_path FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                    $stmt->execute(array_merge($files_to_delete, [$request_id]));
                    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($files as $file) {
                        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM request_files WHERE id IN ($placeholders) AND request_id = ?");
                    $stmt->execute(array_merge($files_to_delete, [$request_id]));
                }

                $pdo->commit();

                $stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
                $stmt->execute([$request_id]);
                $updated_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response = [
                    'success' => 'Заявка обновлена',
                    'description' => $description,
                    'files' => $updated_files
                ];
                error_log('Edit request response: ' . json_encode($response));
                echo json_encode($response);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
                error_log('Edit request error: ' . $e->getMessage());
                echo json_encode(['errors' => $errors]);
            }
        } else {
            error_log('Edit request validation errors: ' . json_encode($errors));
            echo json_encode(['errors' => $errors]);
        }
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_request') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT file_path FROM request_files WHERE request_id = ?');
            $stmt->execute([$request_id]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($files as $file) {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $stmt = $pdo->prepare('DELETE FROM request_files WHERE request_id = ?');
            $stmt->execute([$request_id]);

            $stmt = $pdo->prepare('DELETE FROM requests WHERE id = ? AND user_id = ?');
            $stmt->execute([$request_id, $_SESSION['user_id']]);

            $pdo->commit();

            $response = ['success' => 'Заявка удалена', 'redirect' => '/profile.php'];
            error_log('Delete request response: ' . json_encode($response));
            echo json_encode($response);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Ошибка базы данных: ' . $e->getMessage();
            error_log('Delete request error: ' . $e->getMessage());
            echo json_encode(['errors' => $errors]);
        }
        exit;
    }
}

if ($is_guest_view) {
    $stmt = $pdo->prepare('
        SELECT r.id, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
        FROM requests r
        JOIN services s ON r.service_id = s.id
        WHERE r.id = ?
    ');
    $stmt->execute([$request_id]);
} else {
    $stmt = $pdo->prepare('
        SELECT r.id, r.service_id, r.status, r.created_at, r.description, s.name AS service_name
        FROM requests r
        JOIN services s ON r.service_id = s.id
        WHERE r.id = ? AND r.user_id = ?
    ');
    $stmt->execute([$request_id, $_SESSION['user_id']]);
}
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Заявка не найдена или доступ запрещён']]);
        exit;
    }
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Заявка не найдена или у вас нет прав для её просмотра.'
    ];
    header('Location: /');
    exit;
}

// Fetch Service details
$service = null;
if (isset($request['service_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$request['service_id']]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch User details for the request, if not a guest view
$user = null;
if (!$is_guest_view && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare('SELECT id, file_path FROM request_files WHERE request_id = ?');
$stmt->execute([$request_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Обработка отзыва ---
$review_error = '';
$review_success = '';
$review_text = '';
$can_leave_review = false;
$review = null;

if (isset($request['id'])) {
    // Проверяем, есть ли отзыв по этой заявке
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE request_id = ?');
    $stmt->execute([$request['id']]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    // Можно оставить отзыв, если заявка выполнена, отзыв не оставлен, пользователь авторизован и заявка его
    if (
        isset($_SESSION['user_id']) &&
        !$is_guest_view &&
        $request['status'] === 'completed' &&
        !$review
    ) {
        $can_leave_review = true;
    }

    // Обработка отправки отзыва
    if ($can_leave_review && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
        $review_text = trim($_POST['review_text']);
        if (mb_strlen($review_text) < 5) {
            $review_error = 'Отзыв слишком короткий.';
        } elseif (mb_strlen($review_text) > 1000) {
            $review_error = 'Отзыв слишком длинный (максимум 1000 символов).';
        } else {
            // Получаем имя пользователя по user_id
            $user_name = '';
            $stmt_user = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt_user->execute([$_SESSION['user_id']]);
            if ($row = $stmt_user->fetch()) {
                $user_name = $row['name'];
            }
            $stmt = $pdo->prepare('INSERT INTO reviews (request_id, user_id, author, text) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $request['id'],
                $_SESSION['user_id'],
                $user_name,
                $review_text
            ]);
            $review_success = 'Спасибо за ваш отзыв!';
            // Получаем только что добавленный отзыв
            $stmt = $pdo->prepare('SELECT * FROM reviews WHERE request_id = ?');
            $stmt->execute([$request['id']]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            $can_leave_review = false;
        }
    }
}

require_once 'includes/header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container">
       <div class="section-title">
          <span class="description-title">Детали заявки</span>
          <h2>Заявка №<?= htmlspecialchars($request['id']) ?></h2>
       </div>
    </div>
  </div><!-- End Page Title -->

  <!-- Request Details Section -->
  <section id="request-details" class="request-details section">
    <div class="container">

      <div class="row gy-4">

        <!-- Main Request Info -->
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
          
          <!-- Request View -->
          <div id="request-view-container">
            <div class="request-main-card">
              <div class="card-header">
                <h3>Информация о заявке</h3>
                <div class="status-badge status-<?= htmlspecialchars(strtolower($request['status'])) ?>">
                  <?= htmlspecialchars(ucfirst($request['status'])) ?>
                </div>
              </div>
    <div class="card-body">
                <div class="info-item">
                  <strong>Дата создания:</strong>
                  <span><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></span>
                </div>
                <div class="info-item description-item">
                  <strong>Описание проблемы:</strong>
                  <p id="view-description"><?= nl2br(htmlspecialchars($request['description'])) ?></p>
            </div>

                <div class="info-item">
                  <strong>Прикрепленные файлы:</strong>
                  <div class="attached-files-container" id="view-files">
                    <?php foreach ($files as $file): 
                      $file_path = htmlspecialchars($file['file_path']);
                      $file_name = basename($file_path);
                      $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file_path);
                    ?>
                    <div class="file-item" data-file-id="<?= $file['id'] ?>">
                      <?php if ($is_image): ?>
                        <a href="/<?= $file_path ?>" class="glightbox" data-gallery="request-images">
                          <img src="/<?= $file_path ?>" alt="<?= $file_name ?>" class="file-preview-thumbnail">
                          <span><?= $file_name ?></span>
                        </a>
                <?php else: ?>
                        <a href="/<?= $file_path ?>" target="_blank">
                          <i class="bi bi-file-earmark-zip"></i>
                          <span><?= $file_name ?></span>
                        </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- End Request View -->

          <!-- Request Edit Form -->
          <div id="request-edit-container" style="display: none;">
            <div class="request-main-card">
              <div class="card-header">
                <h3>Редактирование заявки</h3>
              </div>
              <div class="card-body">
                <form id="edit-request-form" novalidate>
                  <div class="info-item description-item">
                    <label for="edit-description" class="form-label"><strong>Описание проблемы:</strong></label>
                    <textarea id="edit-description" name="description" class="form-control" rows="5" required><?= htmlspecialchars($request['description']) ?></textarea>
                    <div class="invalid-feedback">Пожалуйста, введите описание.</div>
                  </div>

                  <div class="info-item">
                    <strong>Управление файлами:</strong>
                    <div id="edit-files-list" class="mb-3">
                      <!-- Existing files with delete checkboxes will be populated by JS -->
                    </div>
                    
                    <label for="add-files" class="form-label"><strong>Добавить новые файлы:</strong></label>
                    <div class="file-upload-wrapper">
                      <input type="file" id="add-files-input" class="form-control" multiple accept=".jpg,.png,.pdf">
                      <label for="add-files-input" class="primary-btn">Выберите файлы...</label>
                    </div>
                    <div id="new-files-preview" class="mt-3"></div>
                  </div>
                  
                  <div id="edit-form-notification" class="alert" style="display:none; margin-bottom: 20px;"></div>

                  <div class="d-flex justify-content-end gap-2">
                    <button type="button" id="cancel-edit-btn" class="btn danger-btn">Отмена</button>
                    <button type="submit" class="btn primary-btn">Сохранить изменения</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <!-- End Request Edit Form -->

          <?php if ($can_leave_review): ?>
          <div class="review-form-card mt-4" data-aos="fade-up" data-aos-delay="200">
              <h3>Оставить отзыв</h3>
              <form action="" method="post">
                  <input type="hidden" name="request_id" value="<?= $request_id ?>">
                  <textarea name="review_text" class="form-control" rows="4" placeholder="Поделитесь вашим мнением о проделанной работе..." required><?= htmlspecialchars($review_text) ?></textarea>
                  <?php if ($review_error): ?>
                      <div class="alert alert-danger mt-2"><?= $review_error ?></div>
                  <?php endif; ?>
                  <button type="submit" class="primary-btn mt-3">Отправить отзыв</button>
              </form>
          </div>
          <?php elseif ($review): ?>
          <div class="review-display-card mt-4" data-aos="fade-up" data-aos-delay="200">
              <h3>Ваш отзыв</h3>
              <p>"<?= nl2br(htmlspecialchars($review['text'])) ?>"</p>
              <small>Оставлен: <?= date('d.m.Y', strtotime($review['created_at'])) ?></small>
          </div>
          <?php endif; ?>

        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <?php if (!$is_guest_view && isset($user)): ?>
          <div class="sidebar-card">
            <h4><i class="bi bi-person-circle"></i> Клиент</h4>
            <ul class="info-list">
              <li><strong>ФИО:</strong> <?= htmlspecialchars($user['name']) ?></li>
              <li><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
              <li><strong>Телефон:</strong> <?= htmlspecialchars($user['phone']) ?></li>
            </ul>
          </div>
          <?php endif; ?>

          <div class="sidebar-card">
            <h4><i class="bi bi-tools"></i> Услуга</h4>
            <?php if ($service): ?>
            <ul class="info-list">
              <li><strong>Название:</strong> <?= htmlspecialchars($service['name']) ?></li>
              <li><strong>Цена:</strong> <?= htmlspecialchars($service['price']) ?> руб.</li>
            </ul>
            <?php else: ?>
            <p>Информация об услуге не найдена.</p>
                <?php endif; ?>
          </div>
          
           <?php if (!$is_guest_view && $request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
            <div class="sidebar-card actions-card">
                <h4><i class="bi bi-pencil-square"></i> Действия</h4>
                <div id="actions-buttons-container">
                    <button id="edit-request-btn" class="btn primary-btn w-100 mb-2">Редактировать</button>
                    <button id="delete-request-btn" class="btn danger-btn w-100" data-id="<?= $request_id ?>">Удалить заявку</button>
                </div>
            </div>
            <?php endif; ?>

        </div>

        </div>
    </div>
  </section>

</main>

<?php
require_once 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewContainer = document.getElementById('request-view-container');
    const editContainer = document.getElementById('request-edit-container');
    const editBtn = document.getElementById('edit-request-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const editForm = document.getElementById('edit-request-form');
    const viewFilesContainer = document.getElementById('view-files');
    const editFilesList = document.getElementById('edit-files-list');
    const actionsButtonsContainer = document.getElementById('actions-buttons-container');
    const deleteBtn = document.getElementById('delete-request-btn');
    const newFilesPreview = document.getElementById('new-files-preview');
    const fileInput = document.getElementById('add-files-input');

    let existingFilesToDelete = new Set();
    let newStagedFiles = [];

    function setActionsLocked(isLocked) {
        if (!actionsButtonsContainer) return;
        
        if (isLocked) {
            actionsButtonsContainer.classList.add('actions-locked');
            if (editBtn) editBtn.disabled = true;
            if (deleteBtn) deleteBtn.disabled = true;
        } else {
            actionsButtonsContainer.classList.remove('actions-locked');
            if (editBtn) editBtn.disabled = false;
            if (deleteBtn) deleteBtn.disabled = false;
        }
    }

    if (editBtn) {
        editBtn.addEventListener('click', () => {
            existingFilesToDelete.clear();
            newStagedFiles = [];
            newFilesPreview.innerHTML = '';
            fileInput.value = '';
            // Populate file list for editing with delete icons
            editFilesList.innerHTML = '';
            const currentFiles = viewFilesContainer.querySelectorAll('.file-item');

            if (currentFiles.length > 0) {
                currentFiles.forEach(fileNode => {
                    const fileId = fileNode.getAttribute('data-file-id');
                    const fileLink = fileNode.querySelector('a');
                    const isImage = fileNode.querySelector('.file-preview-thumbnail');
                    const fileName = fileNode.querySelector('span').textContent;

                    let previewHtml = isImage 
                        ? `<img src="${fileLink.href}" alt="${fileName}" class="file-preview-thumbnail">`
                        : `<i class="bi bi-file-earmark-zip"></i>`;

                    const fileItemHtml = `
                        <div class="edit-file-item" data-file-id="${fileId}">
                            <div class="edit-file-info">
                                ${previewHtml}
                                <span>${fileName}</span>
                            </div>
                            <button type="button" class="delete-file-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    `;
                    editFilesList.innerHTML += fileItemHtml;
                });
            } else {
                editFilesList.innerHTML = '<p>Прикрепленных файлов нет.</p>';
            }
           
            viewContainer.style.display = 'none';
            setActionsLocked(true);
            editContainer.style.display = 'block';
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            viewContainer.style.display = 'block';
            setActionsLocked(false);
            editContainer.style.display = 'none';
        });
    }

    if(fileInput) {
        fileInput.addEventListener('change', e => {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                newStagedFiles.push(file);
                const reader = new FileReader();
                reader.onload = function(event) {
                    const previewWrapper = document.createElement('div');
                    previewWrapper.className = 'edit-file-item new-file-preview';
                    previewWrapper.innerHTML = `
                        <div class="edit-file-info">
                            <img src="${event.target.result}" alt="${file.name}" class="file-preview-thumbnail">
                            <span>${file.name}</span>
                        </div>
                        <button type="button" class="delete-file-btn" data-file-name="${file.name}"><i class="bi bi-trash"></i></button>
                    `;
                    newFilesPreview.appendChild(previewWrapper);
                };
                reader.readAsDataURL(file);
            });
            // Clear the input to allow adding more files
            e.target.value = ''; 
        });
    }

    newFilesPreview.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('.delete-file-btn');
        if(deleteButton) {
            const fileName = deleteButton.getAttribute('data-file-name');
            newStagedFiles = newStagedFiles.filter(f => f.name !== fileName);
            deleteButton.closest('.new-file-preview').remove();
        }
    });

    editFilesList.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('.delete-file-btn');
        if (deleteButton) {
            const fileItem = deleteButton.closest('.edit-file-item');
            const fileId = fileItem.getAttribute('data-file-id');
            
            if (existingFilesToDelete.has(fileId)) {
                existingFilesToDelete.delete(fileId);
                fileItem.classList.remove('marked-for-deletion');
            } else {
                existingFilesToDelete.add(fileId);
                fileItem.classList.add('marked-for-deletion');
            }
        }
    });

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.delete('files[]'); // Remove empty FileList from original input
            formData.append('action', 'edit_request');
            formData.append('files_to_delete', JSON.stringify(Array.from(existingFilesToDelete)));
            
            // Append newly staged files
            newStagedFiles.forEach(file => {
                formData.append('files[]', file, file.name);
            });

            const notification = document.getElementById('edit-form-notification');
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = 'Сохранение...';
            submitButton.disabled = true;

            fetch('/request.php?id=<?= $request_id ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('view-description').innerHTML = data.description.replace(/\\r\\n|\\n|\\r/g, '<br>');
                    
                    let filesHtml = '';
                    if (data.files && data.files.length > 0) {
                        data.files.forEach(file => {
                            const filePath = file.file_path;
                            const fileName = filePath.split('/').pop();
                            const isImage = /\.(jpg|jpeg|png|gif)$/i.test(fileName);
                            
                            filesHtml += `<div class="file-item" data-file-id="${file.id}">`;
                            if (isImage) {
                                filesHtml += `<a href="/${filePath}" class="glightbox" data-gallery="request-images">
                                                <img src="/${filePath}" alt="${fileName}" class="file-preview-thumbnail">
                                                <span>${fileName}</span>
                                            </a>`;
                            } else {
                                filesHtml += `<a href="/${filePath}" target="_blank">
                                                <i class="bi bi-file-earmark-zip"></i>
                                                <span>${fileName}</span>
                                            </a>`;
                            }
                            filesHtml += `</div>`;
                        });
                    }
                    viewFilesContainer.innerHTML = filesHtml;

                    if (typeof GLightbox === 'function') {
                       const lightbox = GLightbox({ selector: '.glightbox' });
                    }
                    
                    viewContainer.style.display = 'block';
                    setActionsLocked(false);
                    editContainer.style.display = 'none';
                    notification.style.display = 'none';
                    
                } else if (data.errors) {
                    let errorHtml = '<ul>';
                    for (const key in data.errors) {
                        errorHtml += '<li>' + (Array.isArray(data.errors[key]) ? data.errors[key].join(', ') : data.errors[key]) + '</li>';
                    }
                    errorHtml += '</ul>';
                    notification.className = 'alert alert-danger';
                    notification.innerHTML = errorHtml;
                    notification.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notification.className = 'alert alert-danger';
                notification.innerHTML = 'Произошла ошибка при отправке. Попробуйте снова.';
                notification.style.display = 'block';
            })
            .finally(() => {
                 submitButton.innerHTML = originalButtonText;
                 submitButton.disabled = false;
            });
        });
    }

    // Delete Logic for the whole request
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            // Check if edit mode is active, if so, do nothing.
            if (editContainer.style.display === 'block') {
                return;
            }
            if (confirm('Вы уверены, что хотите удалить эту заявку? Это действие необратимо.')) {
                const requestId = this.getAttribute('data-id');
                fetch('/request.php?id=' + requestId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete_request'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.success);
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else if (data.errors && data.errors.general) {
                        alert('Ошибка: ' + data.errors.general);
                    } else {
                        alert('Произошла неизвестная ошибка.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при отправке запроса.');
                });
            }
        });
    }
});
</script>