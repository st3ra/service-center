$(document).ready(function() {
    console.log('auth.js loaded');

    let selectedFiles = [];

    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '/login.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#auth-nav').html(response.nav_html);
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul>';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при авторизации').addClass('alert-danger').show();
            }
        });
    });

    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '/register.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#auth-nav').html(response.nav_html);
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul>';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при регистрации').addClass('alert-danger').show();
            }
        });
    });

    $(document).on('click', '[data-action="logout"]', function(e) {
        e.preventDefault();
        $.ajax({
            url: '/logout.php',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    $('#auth-nav').html(response.nav_html);
                    $('#notification').text(response.success).addClass('alert-success').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при выходе').addClass('alert-danger').show();
            }
        });
    });

    $(document).on('click', '#edit-profile-btn', function() {
        $('#profile-view').hide();
        $('#profile-edit-form').show();
    });

    $(document).on('click', '#cancel-edit-btn', function() {
        $('#profile-edit-form').hide();
        $('#profile-view').show();
    });

    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '/profile.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#profile-view').html(`
                        <p><strong>ФИО:</strong> ${response.user_data.name}</p>
                        <p><strong>Телефон:</strong> ${response.user_data.phone}</p>
                        <p><strong>Email:</strong> ${response.user_data.email}</p>
                        <button class="btn btn-outline-primary btn-sm" id="edit-profile-btn"><i class="bi bi-pencil"></i> Редактировать</button>
                    `);
                    $('#profile-edit-form').hide();
                    $('#profile-view').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul>';
                    $.each(response.errors, function(key, error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при редактировании профиля').addClass('alert-danger').show();
            }
        });
    });

    function updateFilePreview() {
        $('#image-preview').empty();
        selectedFiles.forEach((file, index) => {
            const fileContainer = $('<div>').addClass('file-preview me-2 mb-2 position-relative');
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
                fileContainer.append(`<p class="text-muted">${file.name} (не изображение)</p>`);
            }
            const removeBtn = $('<button>').attr({
                type: 'button',
                class: 'btn btn-danger btn-sm position-absolute top-0 end-0',
                'data-index': index
            }).text('×').css({
                'padding': '2px 6px',
                'line-height': '1'
            });
            fileContainer.append(removeBtn);
            $('#image-preview').append(fileContainer);
        });
    }

    $('#file').on('change', function(e) {
        const newFiles = Array.from(e.target.files);
        console.log('Selected files:', newFiles.length);
        selectedFiles = [...selectedFiles, ...newFiles];
        updateFilePreview();
        $(this).val('');
    });

    $(document).on('click', '.file-preview .btn-danger', function() {
        const index = $(this).data('index');
        selectedFiles.splice(index, 1);
        updateFilePreview();
    });

    $('#request-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Request form submitted');
        var formData = new FormData(this);
        var serviceId = $('input[name="service_id"]').val();
        formData.delete('files[]');
        selectedFiles.forEach(file => {
            formData.append('files[]', file);
        });
        console.log('Service ID:', serviceId);
        console.log('FormData files:', formData.getAll('files[]'));
        $.ajax({
            url: '/form.php?service_id=' + encodeURIComponent(serviceId),
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('AJAX response:', response);
                $('#notification').empty().removeClass('alert-success alert-danger');
                $('#request-form .is-invalid').removeClass('is-invalid');
                $('#request-form .invalid-feedback').empty();
                if (response.success) {
                    if (response.is_authenticated) {
                        sessionStorage.setItem('formSuccess', response.success);
                        console.log('Redirecting to /request.php?id=' + response.request_id);
                        window.location.href = '/request.php?id=' + response.request_id;
                    } else {
                        $('#notification').text(response.success).addClass('alert-success').show();
                        setTimeout(function() {
                            window.location.href = '/';
                        }, 2000);
                    }
                } else if (response.errors) {
                    console.log('Errors:', response.errors);
                    $.each(response.errors, function(key, errors) {
                        if (Array.isArray(errors)) {
                            var errorHtml = '<ul>';
                            $.each(errors, function(i, error) {
                                errorHtml += '<li>' + error + '</li>';
                            });
                            errorHtml += '</ul>';
                            $('#request-form [name="files[]"]').addClass('is-invalid');
                            $('#request-form [name="files[]"]').siblings('.invalid-feedback').html(errorHtml);
                        } else {
                            var $input = $('#request-form [name="' + key + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(errors);
                            } else {
                                var errorHtml = '<ul><li>' + errors + '</li></ul>';
                                $('#notification').append(errorHtml).addClass('alert-danger').show();
                            }
                        }
                    });
                    if (response.form_data) {
                        $.each(response.form_data, function(key, value) {
                            $('#request-form [name="' + key + '"]').val(value);
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error, xhr.responseText);
                $('#notification').empty().removeClass('alert-success alert-danger');
                $('#notification').text('Ошибка при отправке заявки: ' + error).addClass('alert-danger').show();
            }
        });
    });

    let originalDescription = $('#description-text').text();
    let newFiles = [];
    let filesToDelete = [];
    let originalFiles = [];

    $(document).on('click', '#edit-request-btn', function() {
        $('#request-view').addClass('editing');
        originalFiles = [];
        $('#file-list .file-item').each(function() {
            const fileId = $(this).data('file-id');
            const filePath = $(this).find('img').attr('src') || $(this).find('a').attr('href');
            originalFiles.push({ id: fileId, file_path: filePath });
            $(this).append(`
                <button class="btn btn-danger btn-sm delete-file-btn" data-file-id="${fileId}">Удалить</button>
            `);
        });
        const descriptionText = $('#description-text').text();
        $('#description-container').html(`
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="4">${descriptionText}</textarea>
                <div class="invalid-feedback"></div>
            </div>
        `);
        $('#file-list').after(`
            <div class="mb-3" id="new-files-container">
                <label for="new-files" class="form-label">Добавить новые файлы (jpg, png, pdf, до 5 МБ)</label>
                <input type="file" class="form-control" id="new-files" name="files[]" multiple accept=".jpg,.png,.pdf">
                <div class="invalid-feedback"></div>
                <div id="new-files-preview" class="mt-2"></div>
            </div>
        `);
        $('#edit-request-btn').hide();
        $('#delete-request-btn').hide();
        $('#request-view').append(`
            <button type="button" class="btn btn-primary" id="save-request-btn">Сохранить</button>
            <button type="button" class="btn btn-secondary ms-2" id="cancel-request-btn">Отмена</button>
        `);
    });

    $(document).on('change', '#new-files', function(e) {
        const files = Array.from(e.target.files);
        newFiles = [...newFiles, ...files];
        $('#new-files-preview').empty();
        newFiles.forEach((file, index) => {
            const fileContainer = $('<div>').addClass('file-preview me-2 mb-2 position-relative');
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
                fileContainer.append(`<p class="text-muted">${file.name} (не изображение)</p>`);
            }
            const removeBtn = $('<button>').attr({
                type: 'button',
                class: 'btn btn-danger btn-sm position-absolute top-0 end-0',
                'data-index': index
            }).text('×').css({
                'padding': '2px 6px',
                'line-height': '1'
            });
            fileContainer.append(removeBtn);
            $('#new-files-preview').append(fileContainer);
        });
        $(this).val('');
    });

    $(document).on('click', '#new-files-preview .btn-danger', function() {
        const index = $(this).data('index');
        newFiles.splice(index, 1);
        $('#new-files-preview').empty();
        newFiles.forEach((file, index) => {
            const fileContainer = $('<div>').addClass('file-preview me-2 mb-2 position-relative');
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
                fileContainer.append(`<p class="text-muted">${file.name} (не изображение)</p>`);
            }
            const removeBtn = $('<button>').attr({
                type: 'button',
                class: 'btn btn-danger btn-sm position-absolute top-0 end-0',
                'data-index': index
            }).text('×').css({
                'padding': '2px 6px',
                'line-height': '1'
            });
            fileContainer.append(removeBtn);
            $('#new-files-preview').append(fileContainer);
        });
    });

    $(document).on('click', '.delete-file-btn', function() {
        const fileId = $(this).data('file-id');
        filesToDelete.push(fileId);
        $(`.file-item[data-file-id="${fileId}"]`).hide();
    });

    $(document).on('click', '#save-request-btn', function() {
        const description = $('#description').val();
        const formData = new FormData();
        formData.append('action', 'edit_request');
        formData.append('description', description);
        formData.append('files_to_delete', JSON.stringify(filesToDelete));
        newFiles.forEach(file => {
            formData.append('files[]', file);
        });

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Save AJAX response:', response);
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    const newDescription = response.description || 'Отсутствует';
                    $('#description-container').html(`
                        <p><strong>Описание:</strong> <span id="description-text">${newDescription}</span></p>
                    `);
                    originalDescription = newDescription;

                    $('#file-list').empty();
                    if (!response.files || response.files.length === 0) {
                        $('#file-list').html('<p>Файлы отсутствуют</p>');
                    } else {
                        response.files.forEach(file => {
                            const fileItem = $('<div>').addClass('file-item mb-2').attr('data-file-id', file.id);
                            if (file.file_path.match(/\.(jpg|png)$/i)) {
                                fileItem.append(`<img src="/${file.file_path}" class="img-thumbnail me-2" style="max-width: 100px; max-height: 100px;">`);
                            } else {
                                fileItem.append(`<a href="/${file.file_path}" target="_blank">${file.file_path.split('/').pop()}</a>`);
                            }
                            $('#file-list').append(fileItem);
                        });
                    }

                    $('#request-view').removeClass('editing');
                    $('#new-files-container').remove();
                    $('#save-request-btn').remove();
                    $('#cancel-request-btn').remove();
                    $('.delete-file-btn').remove();
                    $('#edit-request-btn').show();
                    $('#delete-request-btn').show();

                    newFiles = [];
                    filesToDelete = [];
                    originalFiles = [];

                    $('#notification').text(response.success).addClass('alert-success').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    var errorHtml = '<ul>';
                    $.each(response.errors, function(key, errors) {
                        errorHtml += '<li>' + (Array.isArray(errors) ? errors.join('</li><li>') : errors) + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                console.log('Save AJAX error:', status, error, xhr.responseText);
                $('#notification').text('Ошибка при сохранении заявки').addClass('alert-danger').show();
            }
        });
    });

    $(document).on('click', '#cancel-request-btn', function() {
        $('#description-container').html(`
            <p><strong>Описание:</strong> <span id="description-text">${originalDescription}</span></p>
        `);
        $('#file-list').empty();
        if (originalFiles.length === 0) {
            $('#file-list').html('<p>Файлы отсутствуют</p>');
        } else {
            originalFiles.forEach(file => {
                const fileItem = $('<div>').addClass('file-item mb-2').attr('data-file-id', file.id);
                if (file.file_path.match(/\.(jpg|png)$/i)) {
                    fileItem.append(`<img src="${file.file_path}" class="img-thumbnail me-2" style="max-width: 100px; max-height: 100px;">`);
                } else {
                    fileItem.append(`<a href="${file.file_path}" target="_blank">${file.file_path.split('/').pop()}</a>`);
                }
                $('#file-list').append(fileItem);
            });
        }
        $('#request-view').removeClass('editing');
        $('#new-files-container').remove();
        $('#save-request-btn').remove();
        $('#cancel-request-btn').remove();
        $('.delete-file-btn').remove();
        $('#edit-request-btn').show();
        $('#delete-request-btn').show();
        newFiles = [];
        filesToDelete = [];
    });

    $(document).on('click', '#delete-request-btn', function() {
        if (!confirm('Вы уверены, что хотите удалить заявку?')) {
            return;
        }
        const formData = new FormData();
        formData.append('action', 'delete_request');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Delete AJAX response:', response);
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    sessionStorage.setItem('formSuccess', response.success);
                    window.location.href = response.redirect;
                } else if (response.errors) {
                    var errorHtml = '<ul>';
                    $.each(response.errors, function(key, errors) {
                        errorHtml += '<li>' + (Array.isArray(errors) ? errors.join('</li><li>') : errors) + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#notification').html(errorHtml).addClass('alert-danger').show();
                }
            },
            error: function(xhr, status, error) {
                console.log('Delete AJAX error:', status, error, xhr.responseText);
                $('#notification').text('Ошибка при удалении заявки').addClass('alert-danger').show();
            }
        });
    });

    const successMessage = sessionStorage.getItem('formSuccess');
    if (successMessage) {
        $('#notification').text(successMessage).addClass('alert-success').show();
        setTimeout(function() { $('#notification').fadeOut(); }, 2000);
        sessionStorage.removeItem('formSuccess');
    }
});