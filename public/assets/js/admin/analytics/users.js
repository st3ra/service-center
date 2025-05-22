// JS для аналитики по активности пользователей

function getFilters() {
    const filters = {
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value
    };
    // Добавляем сортировку для топ-5 сотрудников
    const sort = window.topStaffSort || 'comments';
    filters.sort = sort;
    return filters;
}

function buildQuery(params) {
    const esc = encodeURIComponent;
    return Object.keys(params)
        .filter(key => params[key])
        .map(key => esc(key) + '=' + esc(params[key]))
        .join('&');
}

function updateAnalytics() {
    const filters = getFilters();
    let url = 'users.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Метрика: уникальных клиентов
            document.getElementById('unique-clients').textContent = data.uniqueClients;

            // Топ-5 клиентов (список)
            const topList = document.getElementById('top-clients-list');
            topList.innerHTML = '';
            if (!data.topClients || data.topClients.length === 0) {
                topList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.topClients.forEach(c => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${c.name || c.email}</span> <span class="badge bg-secondary ms-2">${c.requests_count}</span>`;
                    topList.appendChild(li);
                });
            }

            // Активность сотрудников (суммарно)
            let staffCommentsSum = 0;
            if (data.staffComments && data.staffComments.length > 0) {
                staffCommentsSum = data.staffComments.reduce((sum, s) => sum + parseInt(s.comments_count), 0);
            }
            document.getElementById('staff-comments').textContent = staffCommentsSum;

            // Заявок обработано сотрудниками (суммарно)
            let staffRequestsSum = 0;
            if (data.staffRequests && data.staffRequests.length > 0) {
                staffRequestsSum = data.staffRequests.reduce((sum, s) => sum + parseInt(s.requests_handled), 0);
            }
            document.getElementById('staff-requests').textContent = staffRequestsSum;

            // Столбчатая диаграмма: количество комментариев по сотрудникам
            if (window.staffBarChart && typeof window.staffBarChart.destroy === 'function') window.staffBarChart.destroy();
            const barCtx = document.getElementById('staffBarChart').getContext('2d');
            window.staffBarChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: data.staffComments.map(s => s.name + (s.role ? ' (' + s.role + ')' : '')),
                    datasets: [{
                        label: 'Комментариев',
                        data: data.staffComments.map(s => s.comments_count),
                        backgroundColor: '#0d6efd',
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Сотрудник' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Комментариев' } }
                    }
                }
            });

            // Таблица: топ-5 клиентов
            const tableBody = document.getElementById('top-clients-table');
            tableBody.innerHTML = '';
            if (!data.topClients || data.topClients.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Нет данных</td></tr>';
            } else {
                data.topClients.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.name || row.email}</td><td>${row.requests_count}</td><td>${parseInt(row.total_sum).toLocaleString('ru-RU', {style: 'currency', currency: 'RUB', maximumFractionDigits: 0})}</td>`;
                    tableBody.appendChild(tr);
                });
            }

            // --- ТОП-5 сотрудников ---
            const topStaffSort = data.topStaffSort || 'comments';
            window.topStaffSort = topStaffSort;
            const topStaffBlock = document.getElementById('top-staff-block');
            if (topStaffBlock) {
                // Кнопки сортировки и таблица
                topStaffBlock.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold">Топ-5 сотрудников</span>
                        <div>
                            <button type="button" class="btn btn-sm ${topStaffSort==='comments'?'btn-primary':'btn-outline-primary'} me-1" id="sort-comments">По комментариям</button>
                            <button type="button" class="btn btn-sm ${topStaffSort==='requests'?'btn-primary':'btn-outline-primary'}" id="sort-requests">По заявкам</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead><tr><th>Сотрудник</th><th>Заявок</th><th>Комментариев</th></tr></thead>
                            <tbody id="top-staff-table">
                                ${(data.topStaff && data.topStaff.length > 0) ? data.topStaff.map(row => `<tr><td>${row.name}</td><td>${row.requests_handled}</td><td>${row.comments_count}</td></tr>`).join('') : '<tr><td colspan="3" class="text-center">Нет данных</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                `;
                // Кнопки сортировки
                document.getElementById('sort-comments').onclick = function() {
                    window.topStaffSort = 'comments';
                    updateAnalytics();
                };
                document.getElementById('sort-requests').onclick = function() {
                    window.topStaffSort = 'requests';
                    updateAnalytics();
                };
            }
            // Диаграмма заявок сотрудников (отдельно)
            if (window.staffRequestsBarChart && typeof window.staffRequestsBarChart.destroy === 'function') window.staffRequestsBarChart.destroy();
            const chartEl = document.getElementById('staffRequestsBarChart');
            if (chartEl && data.staffRequests && data.staffRequests.length > 0) {
                window.staffRequestsBarChart = new Chart(chartEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.staffRequests.map(s => s.name),
                        datasets: [{
                            label: 'Заявок',
                            data: data.staffRequests.map(s => s.requests_handled),
                            backgroundColor: '#198754',
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { title: { display: true, text: 'Сотрудник' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Заявок' } }
                        }
                    }
                });
            }
        })
        .catch(err => {
            document.getElementById('unique-clients').textContent = 'Ошибка';
            document.getElementById('staff-comments').textContent = 'Ошибка';
            document.getElementById('staff-requests').textContent = 'Ошибка';
            document.getElementById('top-clients-list').innerHTML = '<li class="text-danger">Ошибка</li>';
            document.getElementById('top-clients-table').innerHTML = '<tr><td colspan="3" class="text-danger text-center">Ошибка загрузки данных</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);
    updateAnalytics();
}); 