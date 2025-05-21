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
                    // Редирект на главную страницу админки через 1 секунду
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
}); 