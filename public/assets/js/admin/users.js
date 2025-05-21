$(document).ready(function() {
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
                                    <td>${window.adminUtils.escapeHtml(user.name)}</td>
                                    <td>${window.adminUtils.escapeHtml(user.email)}</td>
                                    <td>${window.adminUtils.escapeHtml(user.phone)}</td>
                                    <td>${window.adminUtils.escapeHtml(user.role)}</td>
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
}); 