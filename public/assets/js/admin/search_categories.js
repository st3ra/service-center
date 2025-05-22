$(function() {
    let searchTimeout;
    $('#search-categories').on('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '/admin/categories.php',
                type: 'GET',
                data: { search: searchValue },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json',
                success: function(response) {
                    const tbody = $('#categories-table-body');
                    tbody.empty();
                    if (!response.categories || response.categories.length === 0) {
                        tbody.html('<tr><td colspan="4" class="text-center">Категории не найдены</td></tr>');
                    } else {
                        response.categories.forEach(function(cat) {
                            let actions = '';
                            if (window.isAdminOrEditor) {
                                actions = `
                                    <button type="button" class="btn btn-warning btn-sm edit-btn" data-id="${cat.id}" data-name="${$('<div>').text(cat.name).html()}"><i class="bi bi-pencil"></i> Редактировать</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${cat.id}" data-name="${$('<div>').text(cat.name).html()}"><i class="bi bi-trash"></i> Удалить</button>
                                `;
                            }
                            tbody.append(`
                                <tr>
                                    <td>${cat.id}</td>
                                    <td>${$('<div>').text(cat.name).html()}</td>
                                    <td>${cat.service_count}</td>
                                    <td>
                                        <a href="/admin/services.php?category_id=${cat.id}" class="btn btn-info btn-sm"><i class="bi bi-list"></i> Услуги</a>
                                        ${actions}
                                    </td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка при поиске категорий:', error);
                }
            });
        }, 300);
    });
}); 