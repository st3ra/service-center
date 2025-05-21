$(document).ready(function() {
    // Массив для хранения выбранных файлов
    let selectedFiles = [];
    
    // Обработка загрузки файлов
    $(document).on('change', '#files', function(e) {
        const files = Array.from(e.target.files);
        selectedFiles = [...selectedFiles, ...files];
        updateFilesPreview();
        $(this).val(''); // Очищаем input для возможности выбора тех же файлов
    });
    
    // Функция обновления превью файлов
    function updateFilesPreview() {
        const previewContainer = $('#files-preview');
        previewContainer.empty();
        
        selectedFiles.forEach((file, index) => {
            const fileContainer = $('<div>').addClass('file-preview me-2 mb-2 d-inline-block position-relative');
            if (file.type.match('image/jpeg') || file.type.match('image/png')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = $('<img>').attr({
                        src: e.target.result,
                        class: 'img-thumbnail',
                        style: 'max-width: 100px; max-height: 100px;'
                    });
                    fileContainer.append(img);
                };
                reader.readAsDataURL(file);
            } else {
                fileContainer.append(`<p class="text-muted mb-0">${file.name}</p>`);
            }
            
            // Добавляем кнопку удаления
            const removeBtn = $('<button>').attr({
                type: 'button',
                class: 'btn btn-danger btn-sm position-absolute top-0 end-0',
                'data-index': index
            }).html('&times;').css({
                'padding': '0 6px',
                'line-height': '1.2'
            });
            
            removeBtn.on('click', function() {
                selectedFiles.splice(index, 1);
                updateFilesPreview();
            });
            
            fileContainer.append(removeBtn);
            previewContainer.append(fileContainer);
        });
    }

    // Обработка кнопки "Редактировать" (заявка)
    $(document).on('click', '#edit-request-btn', function() {
        selectedFiles = []; // Очищаем массив выбранных файлов
        $('#request-view').hide();
        $('#request-edit-form').show();
    });

    // Обработка кнопки "Отмена" (заявка)
    $(document).on('click', '#cancel-edit-btn', function() {
        selectedFiles = []; // Очищаем массив выбранных файлов
        $('#request-edit-form').hide();
        $('#request-view').show();
        $('#notification').hide();
        $('#request-edit-form .is-invalid').removeClass('is-invalid');
        $('#request-edit-form .invalid-feedback').empty();
        $('#files-preview').empty();
    });

    // Массив для хранения ID файлов, которые нужно удалить
    let filesToDelete = [];
    
    // Обработка кнопки удаления файла
    $(document).on('click', '.delete-file-btn', function() {
        const fileId = $(this).data('file-id');
        const $fileItem = $(this).closest('.file-item');
        
        if (confirm('Вы уверены, что хотите удалить этот файл?')) {
            filesToDelete.push(fileId);
            $fileItem.fadeOut(300, function() {
                $(this).remove();
                // Если файлов больше нет, показываем сообщение
                if ($('.current-files .file-item:visible').length === 0) {
                    $('.current-files').html('<p>Файлы отсутствуют</p>');
                }
            });
        }
    });

    // Обработка редактирования комментария
    $(document).on('click', '.edit-comment-btn', function() {
        const $comment = $(this).closest('.card');
        const commentId = $comment.data('comment-id');
        const commentText = $comment.find('.card-text').text();
        
        $comment.find('.card-text').hide();
        $comment.find('.edit-comment-btn, .delete-comment-btn').hide();
        
        const $form = $('<form class="edit-comment-form mb-2">')
            .append($('<textarea class="form-control mb-2">').val(commentText))
            .append($('<button type="submit" class="btn btn-primary btn-sm me-2">').text('Сохранить'))
            .append($('<button type="button" class="btn btn-secondary btn-sm cancel-edit-btn">').text('Отмена'));
        
        $comment.find('.card-text').after($form);
        
        $form.find('textarea').focus();
    });
    
    // Отмена редактирования комментария
    $(document).on('click', '.cancel-edit-btn', function() {
        const $comment = $(this).closest('.card');
        $comment.find('.card-text').show();
        $comment.find('.edit-comment-btn, .delete-comment-btn').show();
        $comment.find('.edit-comment-form').remove();
    });
    
    // Сохранение отредактированного комментария
    $(document).on('submit', '.edit-comment-form', function(e) {
        e.preventDefault();
        const $comment = $(this).closest('.card');
        const commentId = $comment.data('comment-id');
        const newComment = $(this).find('textarea').val().trim();
        
        if (!newComment) return;
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'edit_comment',
                comment_id: commentId,
                comment: newComment
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $comment.find('.card-text').text(response.comment.comment).show();
                    $comment.find('.edit-comment-btn, .delete-comment-btn').show();
                    $comment.find('.edit-comment-form').remove();
                    showNotification(response.success, 'success');
                } else if (response.errors) {
                    showNotification(Object.values(response.errors).join('<br>'), 'danger');
                }
            },
            error: function() {
                showNotification('Ошибка при редактировании комментария', 'danger');
            }
        });
    });
    
    // Удаление комментария
    $(document).on('click', '.delete-comment-btn', function() {
        if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) return;
        
        const $comment = $(this).closest('.card');
        const commentId = $comment.data('comment-id');
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'delete_comment',
                comment_id: commentId
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $comment.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#comment-list .card').length === 0) {
                            $('#comment-list').html('<p>Комментарии отсутствуют</p>');
                        }
                    });
                    showNotification(response.success, 'success');
                } else if (response.errors) {
                    showNotification(Object.values(response.errors).join('<br>'), 'danger');
                }
            },
            error: function() {
                showNotification('Ошибка при удалении комментария', 'danger');
            }
        });
    });
    
    // Функция для показа уведомлений
    function showNotification(message, type) {
        $('#notification')
            .removeClass('alert-success alert-danger')
            .addClass('alert-' + type)
            .html(message)
            .show();
        setTimeout(function() { $('#notification').fadeOut(); }, 2000);
    }

    // Обработка формы редактирования заявки
    $('#request-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        // Создаем новый FormData
        const formData = new FormData();
        
        // Добавляем все поля формы
        formData.append('action', 'edit_request');
        formData.append('request_id', requestId);
        
        // Добавляем поля в зависимости от роли
        if ($('.admin-only-fields').length) {
            // Если есть admin-only поля, значит это админ
            formData.append('name', $('#name').val());
            formData.append('email', $('#email').val());
            formData.append('phone', $('#phone').val());
            formData.append('service_id', $('#service_id').val());
            formData.append('description', $('#description').val());
            formData.append('files_to_delete', JSON.stringify(filesToDelete));
            
            // Добавляем выбранные файлы
            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });
        }
        
        // Добавляем статус и комментарий (доступно для всех ролей)
        formData.append('status', $('#status').val());
        const comment = $('#comment').val().trim();
        if (comment) {
            formData.append('comment', comment);
        }
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                $('#request-edit-form .is-invalid').removeClass('is-invalid');
                $('#request-edit-form .invalid-feedback').empty();
                
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    
                    // Очищаем массивы файлов и превью
                    selectedFiles = [];
                    filesToDelete = [];
                    $('#files-preview').empty();
                    $('#files').val('');
                    
                    // Очищаем поле комментария
                    $('#comment').val('');
                    
                    // Обновляем режим просмотра
                    const filesList = response.request_data.files && response.request_data.files.length > 0 
                        ? response.request_data.files.map(file => `
                            <div class="file-item mb-2" data-file-id="${escapeHtml(file.id)}">
                                ${file.file_path.match(/\.(jpg|jpeg|png|gif)$/i) 
                                    ? `<img src="/${escapeHtml(file.file_path)}" class="img-thumbnail me-2" style="max-width: 100px; max-height: 100px;">` 
                                    : `<a href="/${escapeHtml(file.file_path)}" target="_blank">${escapeHtml(file.file_path.split('/').pop())}</a>`
                                }
                            </div>
                        `).join('') 
                        : '<p>Файлы отсутствуют</p>';

                    // Сохраняем текущий HTML режима просмотра
                    const currentViewHtml = $('#request-view').html();
                    
                    // Обновляем основную информацию
                    let viewHtml = '';
                    if ($('.admin-only-fields').length) {
                        // Для админа показываем всю информацию
                        viewHtml = `
                            <p><strong>Пользователь:</strong> ${escapeHtml(response.request_data.name)}</p>
                            <p><strong>Email:</strong> ${escapeHtml(response.request_data.email)}</p>
                            <p><strong>Телефон:</strong> ${escapeHtml(response.request_data.phone)}</p>
                            <p><strong>Услуга:</strong> ${escapeHtml(response.request_data.service_name)}</p>
                            <p><strong>Статус:</strong> <span id="status-text">${escapeHtml(response.request_data.status)}</span></p>
                            <p><strong>Дата создания:</strong> ${escapeHtml(response.request_data.created_at)}</p>
                            <p><strong>Описание:</strong> <span id="description-text">${escapeHtml(response.request_data.description || 'Отсутствует')}</span></p>
                            <h6>Файлы:</h6>
                            <div id="file-list" class="mb-3">
                                ${filesList}
                            </div>
                            <button class="btn btn-outline-primary btn-sm" id="edit-request-btn"><i class="bi bi-pencil"></i> Редактировать</button>
                            <button class="btn btn-outline-danger btn-sm ms-2" id="delete-request-btn"><i class="bi bi-trash"></i> Удалить заявку</button>
                        `;
                    } else {
                        // Для работника обновляем только статус и сохраняем текущий HTML
                        $('#status-text').text(response.request_data.status);
                        viewHtml = currentViewHtml.replace(
                            /<span id="status-text">[^<]*<\/span>/,
                            `<span id="status-text">${escapeHtml(response.request_data.status)}</span>`
                        );
                    }
                    
                    $('#request-view').html(viewHtml);
                    
                    // Если был добавлен комментарий, обновляем список комментариев
                    if (response.comment) {
                        if ($('#comment-list p').text() === 'Комментарии отсутствуют') {
                            $('#comment-list').empty();
                        }
                        const isAdmin = $('.admin-only-fields').length > 0;
                        const currentUserId = $('#request-edit-form').data('user-id');
                        const canEdit = isAdmin || (response.comment.user_id == currentUserId);
                        
                        $('#comment-list').prepend(`
                            <div class="card mb-2" data-comment-id="${response.comment.id}">
                                <div class="card-body">
                                    <p class="card-text">${escapeHtml(response.comment.comment)}</p>
                                    <p class="card-subtitle text-muted">
                                        <strong>${escapeHtml(response.comment.author)}</strong>, 
                                        ${escapeHtml(response.comment.created_at)}
                                    </p>
                                    ${canEdit ? `
                                        <button class="btn btn-outline-primary btn-sm edit-comment-btn">Редактировать</button>
                                        <button class="btn btn-outline-danger btn-sm delete-comment-btn">Удалить</button>
                                    ` : ''}
                                </div>
                            </div>
                        `);
                    }
                    
                    $('#request-edit-form').hide();
                    $('#request-view').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    $.each(response.errors, function(key, error) {
                        if (Array.isArray(error)) {
                            error.forEach(function(err) {
                                $('#notification').append(`<div class="alert-message">${err}</div>`).addClass('alert-danger').show();
                            });
                        } else {
                            var $input = $('#request-edit-form [name="' + key + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(error);
                            } else {
                                $('#notification').append(`<div class="alert-message">${error}</div>`).addClass('alert-danger').show();
                            }
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при редактировании заявки').addClass('alert-danger').show();
                console.error('Ajax error:', status, error);
                console.error('Response:', xhr.responseText);
            }
        });
    });

    // Обработка формы изменения статуса
    $('#status-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        formData.push({ name: 'request_id', value: requestId });
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: $.param(formData),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#status-text').text(response.status);
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul class="mb-0">';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при обновлении статуса').addClass('alert-danger').show();
            }
        });
    });

    // Обработка формы добавления комментария
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        formData.push({ name: 'request_id', value: requestId });
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: $.param(formData),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#comment-list').prepend(`
                        <div class="card mb-2" data-comment-id="${response.comment.id}">
                            <div class="card-body">
                                <p class="card-text">${escapeHtml(response.comment.comment)}</p>
                                <p class="card-subtitle text-muted">
                                    <strong>${escapeHtml(response.comment.author)}</strong>, 
                                    ${escapeHtml(response.comment.created_at)}
                                </p>
                                <button class="btn btn-outline-primary btn-sm edit-comment-btn">Редактировать</button>
                                <button class="btn btn-outline-danger btn-sm delete-comment-btn">Удалить</button>
                            </div>
                        </div>
                    `);
                    $('#comment').val('');
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul class="mb-0">';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при добавлении комментария').addClass('alert-danger').show();
            }
        });
    });

    // Обработка удаления заявки
    $(document).on('click', '#delete-request-btn', function() {
        if (!confirm('Вы уверены, что хотите удалить эту заявку? Это действие необратимо.')) return;
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { 
                action: 'delete_request',
                request_id: requestId
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                } else if (response.errors) {
                    var errorHtml = '<ul class="mb-0">';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при удалении заявки').addClass('alert-danger').show();
            }
        });
    });

    // Функция для экранирования HTML
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        
        const div = document.createElement('div');
        div.textContent = unsafe;
        return div.innerHTML;
    }
}); 