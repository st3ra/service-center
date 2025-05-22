$(function() {
    let searchTimeout;
    $('#search-services').on('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        const categoryId = $('select[name="category_id"]').val() || 0;
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '/admin/services.php',
                type: 'GET',
                data: { search: searchValue, category_id: categoryId },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json',
                success: function(response) {
                    const tbody = $('#services-table-body');
                    tbody.empty();
                    if (!response.services || response.services.length === 0) {
                        tbody.html('<tr><td colspan="7" class="text-center">Услуги не найдены</td></tr>');
                    } else {
                        response.services.forEach(function(srv) {
                            let actions = '';
                            if (window.isAdminOrEditor) {
                                actions = `<a href="/admin/edit_service.php?id=${srv.id}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Редактировать</a>`;
                            }
                            tbody.append(`
                                <tr>
                                    <td>${srv.id}</td>
                                    <td>${$('<div>').text(srv.name).html()}</td>
                                    <td>${$('<div>').text(srv.category_name).html()}</td>
                                    <td>${$('<div>').text(srv.description.length > 80 ? srv.description.substr(0, 80) + '...' : srv.description).html()}</td>
                                    <td>${Number(srv.price).toLocaleString('ru-RU', {minimumFractionDigits: 2})} ₽</td>
                                    <td>${srv.image_path ? $('<div>').text(srv.image_path).html() : '-'}</td>
                                    <td>${actions}</td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка при поиске услуг:', error);
                }
            });
        }, 300);
    });
}); 