$(document).ready(function() {
    // Обработка кнопки "Редактировать"
    $(document).on('click', '#edit-profile-btn', function() {
        $('#profile-view').hide();
        $('#profile-edit-form').show();
    });

    // Обработка кнопки "Отмена"
    $(document).on('click', '#cancel-edit-btn', function() {
        $('#profile-edit-form').hide();
        $('#profile-view').show();
        $('#notification').hide();
        $('#profile-edit-form .is-invalid').removeClass('is-invalid');
        $('#profile-edit-form .invalid-feedback').empty();
    });

    // Обработка формы редактирования профиля
    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                $('#profile-edit-form .is-invalid').removeClass('is-invalid');
                $('#profile-edit-form .invalid-feedback').empty();
                
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    // Обновляем данные в режиме просмотра
                    $('#profile-view').html(`
                        <p><strong>ID:</strong> ${response.user_data.id}</p>
                        <p><strong>Имя:</strong> ${response.user_data.name}</p>
                        <p><strong>Email:</strong> ${response.user_data.email}</p>
                        <p><strong>Телефон:</strong> ${response.user_data.phone}</p>
                        <p><strong>Роль:</strong> ${response.user_data.role}</p>
                        <p><strong>Количество заявок:</strong> ${response.user_data.request_count}</p>
                        ${response.is_admin ? `
                            <button class="btn btn-outline-primary btn-sm" id="edit-profile-btn">
                                <i class="bi bi-pencil"></i> Редактировать
                            </button>
                        ` : ''}
                    `);
                    $('#profile-edit-form').hide();
                    $('#profile-view').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                } else if (response.errors) {
                    $.each(response.errors, function(key, error) {
                        var $input = $('#profile-edit-form [name="' + key + '"]');
                        if ($input.length) {
                            $input.addClass('is-invalid');
                            $input.siblings('.invalid-feedback').text(error);
                        } else {
                            var errorHtml = '<ul class="mb-0"><li>' + error + '</li></ul>';
                            $('#notification').append(errorHtml).addClass('alert-danger').show();
                        }
                    });
                    // Сохраняем введенные данные
                    $.each(response.user_data, function(key, value) {
                        $('#profile-edit-form [name="' + key + '"]').val(value);
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#notification').text('Ошибка при редактировании профиля').addClass('alert-danger').show();
            }
        });
    });
}); 