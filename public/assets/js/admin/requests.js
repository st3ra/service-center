$(document).ready(function() {
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
                                <td>${window.adminUtils.escapeHtml(request.user_name || request.user_email)}</td>
                                <td>${window.adminUtils.escapeHtml(request.service_name)}</td>
                                <td>${window.adminUtils.escapeHtml(request.status)}</td>
                                <td>${window.adminUtils.escapeHtml(request.created_at)}</td>
                                <td>${window.adminUtils.escapeHtml(request.description ? request.description.substring(0, 100) : '')}${request.description && request.description.length > 100 ? '...' : ''}</td>
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