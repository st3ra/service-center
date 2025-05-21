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

    // Состояние фильтров и сортировки
    let currentState = { ...initialState };

    // Функция обновления таблицы
    function updateRequestsTable(newState = null) {
        if (newState) {
            currentState = { ...currentState, ...newState };
        }

        // Показываем индикатор загрузки
        $('#requests-table-body').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div></td></tr>');

            $.ajax({
            url: window.location.pathname,
                type: 'GET',
            data: currentState,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json',
                success: function(response) {
                    const tbody = $('#requests-table-body');
                    tbody.empty();
                    
                    if (response.requests.length === 0) {
                        tbody.html('<tr><td colspan="7" class="text-center">Заявки не найдены</td></tr>');
                    } else {
                        response.requests.forEach(function(request) {
                            tbody.append(`
                                <tr>
                                    <td>${request.id}</td>
                                    <td>${escapeHtml(request.user_name || request.user_email)}</td>
                                    <td>${escapeHtml(request.service_name)}</td>
                                    <td>${escapeHtml(request.status)}</td>
                                    <td>${escapeHtml(request.created_at)}</td>
                                    <td>${escapeHtml(request.description ? request.description.substring(0, 100) : '')}${request.description && request.description.length > 100 ? '...' : ''}</td>
                                    <td>
                                        <a href="/admin/request.php?id=${request.id}" class="btn btn-info btn-sm">Просмотр</a>
                                    </td>
                                </tr>
                            `);
                        });
                    }

                // Обновляем активные состояния кнопок сортировки
                $('.sort-btn').removeClass('active');
                $(`.sort-btn[data-field="${currentState.sort_field}"][data-order="${currentState.sort_order}"]`).addClass('active');

                // Обновляем URL без перезагрузки страницы
                const url = new URL(window.location.href);
                Object.entries(currentState).forEach(([key, value]) => {
                    if (value) {
                        url.searchParams.set(key, value);
                    }
                });
                window.history.pushState(currentState, '', url);
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка при загрузке заявок:', error);
                $('#requests-table-body').html('<tr><td colspan="7" class="text-center text-danger">Ошибка при загрузке данных</td></tr>');
            }
        });
    }

    // Обработка кнопок сортировки
    $(document).on('click', '.sort-btn', function(e) {
        e.preventDefault();
        const field = $(this).data('field');
        const order = $(this).data('order');
        updateRequestsTable({ sort_field: field, sort_order: order });
    });

    // Обработка изменений в фильтрах
    $(document).on('change', '#filter-status', function() {
        updateRequestsTable({ status: $(this).val() });
    });

    $(document).on('change', '#filter-service', function() {
        updateRequestsTable({ service_id: $(this).val() });
    });

    // Обработка изменения дат с debounce
    let dateTimeout;
    $(document).on('change', '#filter-date-from, #filter-date-to', function() {
        clearTimeout(dateTimeout);
        const dateFrom = $('#filter-date-from').val();
        const dateTo = $('#filter-date-to').val();
        
        // Очищаем предыдущие ошибки
        $('.date-error').remove();
        $('#filter-date-from, #filter-date-to').removeClass('is-invalid');
        
        // Проверяем валидность дат
        if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
            $('#filter-date-to')
                .addClass('is-invalid')
                .after('<div class="invalid-feedback date-error">Дата "До" должна быть позже даты "От"</div>');
            return;
        }
        
        dateTimeout = setTimeout(() => {
            updateRequestsTable({
                date_from: dateFrom,
                date_to: dateTo
            });
        }, 300);
    });

    // Закрытие всех открытых фильтров при клике вне них
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.column-header').length) {
            $('.filter-popup').hide();
        }
    });

    // Обработка кнопок фильтрации
    $('.filter-btn').on('click', function(e) {
        e.stopPropagation();
        const filterId = $(this).data('filter');
        const popup = $(`#${filterId}-filter`);
        
        // Закрываем все остальные фильтры
        $('.filter-popup').not(popup).hide();
        
        // Переключаем текущий фильтр
        popup.toggle();
        
        // Позиционируем попап под кнопкой
        if (popup.is(':visible')) {
            const btn = $(this);
            const btnPos = btn.offset();
            popup.css({
                top: btnPos.top + btn.outerHeight() + 5,
                left: btnPos.left
            });
        }
    });

    // Функция для экранирования HTML
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        
        const div = document.createElement('div');
        div.textContent = unsafe;
        return div.innerHTML;
    }
});