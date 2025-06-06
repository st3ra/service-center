<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Проверяем, является ли запрос AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    require_once 'includes/handlers/form_handler.php';
    if (!isset($_POST['service_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['errors' => ['general' => 'Не указан ID услуги']]);
        exit;
    }
    $service_id = (int)$_POST['service_id'];
    $user = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    $result = handle_form_submission($pdo, $service_id, $user);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}


if (!isset($_GET['service_id'])) {
    die('Не указан ID услуги');
}

$service_id = (int)$_GET['service_id'];
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die('Услуга не найдена');
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

require_once 'includes/header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade-up">
    <div class="container">
      <span class="description-title">Форма заявки</span>
      <h2>Запись на услугу</h2>
    </div>
  </div><!-- End Page Title -->

  <!-- Form Section -->
  <section id="form-section" class="form-section section">
    <div class="container">
      <div class="row gy-4">
        
        <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
          <form id="request-form" class="php-request-form">
            <div id="notification" style="display:none; margin-bottom: 20px;"></div>
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <div class="row gy-4">
                <?php if ($user): ?>
                    <div class="col-md-6">
                        <label for="name" class="form-label">ФИО</label>
                        <input type="text" class="form-control readonly-styled" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" readonly>
                    </div>
                     <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control readonly-styled" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="col-md-12">
                         <label for="phone" class="form-label">Телефон</label>
                        <input type="text" class="form-control readonly-styled" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" readonly>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <label for="name" class="form-label">ФИО</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-12">
                        <label for="phone" class="form-label">Телефон</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                        <div class="invalid-feedback"></div>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label for="description" class="form-label">Описание проблемы</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="col-12">
                    <label class="form-label"><strong>Прикрепить файлы</strong> (jpg, png, pdf, до 5 МБ)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="files-input" class="form-control" multiple accept=".jpg,.png,.pdf">
                        <label for="files-input" class="primary-btn">Выберите файлы...</label>
                    </div>
                    <div id="new-files-preview" class="mt-3"></div>
                    <div class="invalid-feedback" id="files-feedback" style="display: none;"></div>
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn primary-btn">Отправить заявку</button>
                </div>
            </div>
          </form>
        </div>

        <div class="col-lg-5" data-aos="fade-up" data-aos-delay="200">
            <div class="service-info-card">
                <h3>Выбранная услуга</h3>
                <h2><?= htmlspecialchars($service['name']) ?></h2>
                <p class="service-price">
                    <strong>Ориентировочная цена:</strong> <?= htmlspecialchars($service['price']) ?> руб.
                </p>
                <div class="service-description-box">
                    <p><?= htmlspecialchars($service['description']) ?></p>
                </div>
                <div class="card-footer-note">
                    <p><small>Точная стоимость будет определена после диагностики. Наш менеджер свяжется с вами для уточнения деталей.</small></p>
                </div>
            </div>
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
    const form = document.getElementById('request-form');
    if (!form) return;

    const fileInput = document.getElementById('files-input');
    const newFilesPreview = document.getElementById('new-files-preview');
    const filesFeedback = document.getElementById('files-feedback');
    const notificationContainer = document.getElementById('notification');
    const submitButton = form.querySelector('button[type="submit"]');

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    let newStagedFiles = [];

    // --- Utility Functions for UI feedback ---
    function showGlobalMessage(message, type = 'error-message') {
        notificationContainer.className = `alert ${type}`;
        notificationContainer.innerHTML = message;
        notificationContainer.style.display = 'block';
    }

    function hideGlobalMessage() {
        notificationContainer.style.display = 'none';
        notificationContainer.innerHTML = '';
    }
    
    function showFileError(message) {
        filesFeedback.textContent = message;
        filesFeedback.style.display = 'block';
    }

    function hideFileError() {
        filesFeedback.style.display = 'none';
        filesFeedback.textContent = '';
    }
    
    function resetFieldErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    function displayFieldErrors(errors) {
        for (const [key, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = message;
                }
            } else if (key === 'files') {
                showFileError(Array.isArray(message) ? message.join('<br>') : message);
            } else if (key === 'general') {
                showGlobalMessage(message);
            }
        }
    }
    
    // --- File Handling ---
    if (fileInput) {
        fileInput.addEventListener('change', e => {
            hideGlobalMessage();
            hideFileError();

            for (const file of e.target.files) {
                if (file.size > MAX_FILE_SIZE) {
                    showFileError(`Файл "${file.name}" слишком большой (макс. 5 МБ).`);
                    continue;
                }
                if (newStagedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    continue; // Skip duplicate
                }
                newStagedFiles.push(file);
                renderFilePreview(file);
            }
            e.target.value = ''; // Reset for re-selection of the same file
        });
    }

    function renderFilePreview(file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const previewWrapper = document.createElement('div');
            previewWrapper.className = 'edit-file-item new-file-preview';
            
            const isImage = file.type.startsWith('image/');
            const previewIcon = isImage 
                ? `<img src="${event.target.result}" alt="${file.name}" class="file-preview-thumbnail">`
                : `<i class="bi bi-file-earmark-text" style="font-size: 40px;"></i>`;

            previewWrapper.innerHTML = `
                <div class="edit-file-info">
                    ${previewIcon}
                    <span>${file.name}</span>
                </div>
                <button type="button" class="delete-file-btn" data-file-name="${file.name}"><i class="bi bi-trash"></i></button>
            `;
            newFilesPreview.appendChild(previewWrapper);
        };

        if (file.type.startsWith('image/')) {
            reader.readAsDataURL(file);
        } else {
             reader.onload({ target: { result: null } }); // Call onload directly for non-images
        }
    }

    newFilesPreview.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('.delete-file-btn');
        if (deleteButton) {
            hideFileError();
            const fileName = deleteButton.dataset.fileName;
            newStagedFiles = newStagedFiles.filter(f => f.name !== fileName);
            deleteButton.closest('.new-file-preview').remove();
        }
    });

    // --- Form Submission ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideGlobalMessage();
        hideFileError();
        resetFieldErrors();

        const formData = new FormData(form);
        formData.delete('files[]');
        newStagedFiles.forEach(file => formData.append('files[]', file, file.name));
        
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = 'Отправка... <i class="bi bi-arrow-repeat"></i>';
        submitButton.disabled = true;

        fetch('/form.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.request_id) {
                // Set sessionStorage so the global script in auth.js can display the message
                sessionStorage.setItem('formSuccess', data.success);
                // Redirect to the new request page.
                window.location.href = `/request.php?id=${data.request_id}`;
            } else if (data.errors) {
                displayFieldErrors(data.errors);
                 if (!data.errors.general) { // If there's no general error, show a more specific one
                    showGlobalMessage('Пожалуйста, исправьте ошибки в форме.');
                }
            } else {
                showGlobalMessage('Произошла неизвестная ошибка.');
            }
        })
        .catch(error => {
            console.error('Submission Error:', error);
            showGlobalMessage('Ошибка сети. Не удалось отправить заявку.');
        })
        .finally(() => {
            // Re-enable button only if there was an error and no redirect
            if (!submitButton.disabled || window.location.href.includes('/request.php')) {
                 submitButton.innerHTML = originalButtonText;
                 submitButton.disabled = false;
            }
        });
    });
});
</script>