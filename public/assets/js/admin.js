$(document).ready(function() {
    console.log('admin.js loaded');

    // Обработка формы входа в админ-панель
    $('#admin-login-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: '/admin/login.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                $('#notification').empty().removeClass('alert-success alert-danger');
                
                if (response.success) {
                    $('#notification').text('Вход успешен').addClass('alert-success').show();
                    setTimeout(function() {
                        window.location.href = '/admin/index.php';
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
                $('#notification').text('Ошибка при попытке входа').addClass('alert-danger').show();
            }
        });
    });

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

    // Живой поиск пользователей
    let searchTimeout;
    $('#search-users').on('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '/admin/users.php',
                type: 'GET',
                data: { search: searchValue },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json',
                success: function(response) {
                    const tbody = $('#users-table-body');
                    tbody.empty();
                    
                    if (response.users.length === 0) {
                        tbody.html('<tr><td colspan="7" class="text-center">Пользователи не найдены</td></tr>');
                    } else {
                        response.users.forEach(function(user) {
                            tbody.append(`
                                <tr>
                                    <td>${user.id}</td>
                                    <td>${escapeHtml(user.name)}</td>
                                    <td>${escapeHtml(user.email)}</td>
                                    <td>${escapeHtml(user.phone)}</td>
                                    <td>${escapeHtml(user.role)}</td>
                                    <td>${user.request_count}</td>
                                    <td>
                                        <a href="/admin/user_profile.php?id=${user.id}" class="btn btn-info btn-sm">Просмотр</a>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка при поиске пользователей:', error);
                }
            });
        }, 300); // Задержка 300мс перед отправкой запроса
    });

    // Функция для экранирования HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});