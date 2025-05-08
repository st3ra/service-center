$(document).ready(function() {
    // Обработка формы авторизации
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

    // Обработка формы регистрации
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

    // Обработка выхода
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

    // Переключение на редактирование профиля (делегирование событий)
    $(document).on('click', '#edit-profile-btn', function() {
        $('#profile-view').hide();
        $('#profile-edit-form').show();
    });

    // Отмена редактирования профиля
    $(document).on('click', '#cancel-edit-btn', function() {
        $('#profile-edit-form').hide();
        $('#profile-view').show();
    });

    // Обработка формы редактирования профиля
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

    // Переключение на редактирование заявки (делегирование событий)
    $(document).on('click', '#edit-request-btn', function() {
        $('#request-view').hide();
        $('#request-edit-form').show();
    });

    // Отмена редактирования заявки
    $(document).on('click', '#cancel-request-edit-btn', function() {
        $('#request-edit-form').hide();
        $('#request-view').show();
    });

    // Обработка формы редактирования заявки
    $('#request-edit-form').on('submit', function(e) {
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
                if (response.success) {
                    $('#notification').text(response.success).addClass('alert-success').show();
                    $('#request-view').find('p:contains("Описание")').html(`<strong>Описание:</strong> ${response.request_data.description || 'Отсутствует'}`);
                    $('#request-edit-form').hide();
                    $('#request-view').show();
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
                $('#notification').text('Ошибка при редактировании заявки').addClass('alert-danger').show();
            }
        });
    });
});