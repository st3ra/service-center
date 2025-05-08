$(document).ready(function() {
    // Обработка формы авторизации
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Login form submitted');
        var formData = $(this).serialize();
        $.ajax({
            url: '/login.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                console.log('Login response:', response);
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
                console.log('Login AJAX error:', xhr.responseText);
                $('#notification').text('Ошибка при авторизации').addClass('alert-danger').show();
            }
        });
    });

    // Обработка формы регистрации
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Register form submitted');
        var formData = $(this).serialize();
        $.ajax({
            url: '/register.php',
            type: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                console.log('Register response:', response);
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
                console.log('Register AJAX error:', xhr.responseText);
                $('#notification').text('Ошибка при регистрации').addClass('alert-danger').show();
            }
        });
    });

    // Обработка выхода
    $(document).on('click', '[data-action="logout"]', function(e) {
        e.preventDefault();
        console.log('Logout clicked');
        $.ajax({
            url: '/logout.php',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(response) {
                console.log('Logout response:', response);
                $('#notification').empty().removeClass('alert-success alert-danger');
                if (response.success) {
                    $('#auth-nav').html(response.nav_html);
                    $('#notification').text(response.success).addClass('alert-success').show();
                    setTimeout(function() { $('#notification').fadeOut(); }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.log('Logout AJAX error:', xhr.responseText);
                $('#notification').text('Ошибка при выходе').addClass('alert-danger').show();
            }
        });
    });
});