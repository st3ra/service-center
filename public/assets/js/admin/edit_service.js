$(function() {
    console.log('edit_service.js инициализирован');
    
    // Определяем, находимся ли мы в режиме добавления новой услуги
    const isNewMode = new URLSearchParams(window.location.search).get('new') === '1';
    
    // Включение режима редактирования
    $('#edit-service-btn').on('click', function() {
        $('#service-view').hide();
        $('#service-edit-form').show();
        $('#service-edit-form input, #service-edit-form select, #service-edit-form textarea').prop('disabled', false);
        $('#service-edit-form button[type=submit]').prop('disabled', false);
        $('#delete-service-btn').show();
    });
    
    // Отмена редактирования
    $('#cancel-edit-btn').on('click', function() {
        $('#service-edit-form').hide();
        $('#service-edit-form input, #service-edit-form select, #service-edit-form textarea').prop('disabled', true);
        $('#service-edit-form button[type=submit]').prop('disabled', true);
        $('#delete-service-btn').hide();
        $('#service-view').show();
        $('#notification').hide();
        $('#service-edit-form .is-invalid').removeClass('is-invalid');
        $('#service-edit-form .invalid-feedback').empty();
    });
    
    // Ajax submit формы (FormData для файлов)
    $('#service-edit-form').on('submit', function(e) {
        e.preventDefault();
        console.log('submit service-edit-form');
        var $form = $(this);
        var formData = new FormData(this);
        // Устанавливаем action
        if (!formData.has('action')) {
            formData.append('action', isNewMode ? 'add_service' : 'edit_service');
        } else if (isNewMode) {
            formData.set('action', 'add_service');
        }
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('ajax success', response);
                $('#notification').empty().removeClass('alert-success alert-danger').show();
                $('#service-edit-form .is-invalid').removeClass('is-invalid');
                $('#service-edit-form .invalid-feedback').empty();
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success');
                    if (response.redirect || isNewMode) {
                        setTimeout(function() {
                            window.location.href = response.redirect || '/admin/services.php';
                        }, 1000);
                        return;
                    }
                    // Обновляем режим просмотра для редактирования
                    $('#view-name').text(response.service_data.name);
                    $('#view-category').text(response.service_data.category_name);
                    $('#view-description').html(nl2br(response.service_data.description));
                    $('#view-price').text(Number(response.service_data.price).toLocaleString('ru-RU', {minimumFractionDigits: 2}) + ' ₽');
                    // Обновляем изображение в режиме просмотра
                    if (response.service_data.image_path) {
                        $('#view-image-path').html('<img src="/' + response.service_data.image_path + '" alt="" style="max-width:120px;max-height:120px;"><div>' + response.service_data.image_path + '</div>');
                    } else {
                        $('#view-image-path').html('<span class="text-muted">Нет изображения</span>');
                    }
                    // Обновляем текущее изображение в форме редактирования
                    if ($('#edit-image-preview').length) {
                        $('#edit-image-preview img').attr('src', '/' + response.service_data.image_path);
                        $('#edit-image-preview div').text(response.service_data.image_path);
                    }
                    // Удаляем предпросмотр нового изображения
                    $('#new-image-preview').remove();
                    $('#service-edit-form').hide();
                    $('#service-edit-form input, #service-edit-form select, #service-edit-form textarea').prop('disabled', true);
                    $('#service-edit-form button[type=submit]').prop('disabled', true);
                    $('#delete-service-btn').hide();
                    $('#service-view').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    if (response.errors.general) {
                        $('#notification').text(response.errors.general).addClass('alert-danger').show();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('ajax error', status, error);
                $('#notification').text('Ошибка при сохранении услуги').addClass('alert-danger').show();
            }
        });
    });
    
    // Ajax удаление
    $('#delete-service-btn, #deleteModal button[type=submit]').on('click', function(e) {
        if ($(this).attr('type') === 'submit') {
            e.preventDefault();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { action: 'delete_service' },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.errors && response.errors.general) {
                        $('#notification').text(response.errors.general).addClass('alert-danger').show();
                        $('#deleteModal').modal('hide');
                    }
                },
                error: function() {
                    $('#notification').text('Ошибка при удалении услуги').addClass('alert-danger').show();
                }
            });
        }
    });
    
    function nl2br(str) {
        return str ? str.replace(/\n/g, '<br>') : '';
    }

    // Предпросмотр нового изображения при выборе файла
    $(document).on('change', 'input[name="image_file"]', function() {
        const input = this;
        const $preview = $('#edit-image-preview');
        // Удаляем старый предпросмотр нового изображения, если был
        $('#new-image-preview').remove();
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Вставляем новый предпросмотр перед текущим
                $('<div class="mb-3" id="new-image-preview">'
                    + '<label class="form-label">Новое изображение</label><br>'
                    + '<img src="' + e.target.result + '" alt="" style="max-width:120px;max-height:120px;">'
                    + '</div>').insertBefore($preview.length ? $preview : $(input));
            };
            reader.readAsDataURL(input.files[0]);
        }
    });
}); 