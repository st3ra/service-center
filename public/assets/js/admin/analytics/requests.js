// JS для аналитики по заявкам 

function fetchCategories() {
    return fetch('requests.php?action=categories')
        .then(res => res.json());
}

function fetchServices(categoryId) {
    return fetch('requests.php?action=services&category_id=' + encodeURIComponent(categoryId))
        .then(res => res.json());
}

function getFilters() {
    const filters = {
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        status: document.getElementById('status').value,
        category: document.getElementById('category').value,
        service: document.getElementById('service').value
    };
    console.log('Текущие фильтры:', filters);
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
    let url = 'requests.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;
    console.log('Делаю запрос:', url);

    fetch(url)
        .then(res => {
            console.log('Ответ сервера status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Ответ сервера (JSON):', data);
            // Метрики
            document.getElementById('total-requests').textContent = data.total;
            document.getElementById('requests-week').textContent = data.requestsWeek;
            document.getElementById('requests-month').textContent = data.requestsMonth;

            // Круговая диаграмма (Pie) — распределение по статусам
            if (window.statusPieChart && typeof window.statusPieChart.destroy === 'function') window.statusPieChart.destroy();
            const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
            window.statusPieChart = new Chart(statusPieCtx, {
                type: 'pie',
                data: {
                    labels: data.pieLabels,
                    datasets: [{
                        data: [data.statusDistribution.new, data.statusDistribution.in_progress, data.statusDistribution.completed],
                        backgroundColor: ['#0d6efd', '#ffc107', '#198754'],
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Линейный график — динамика по дням
            if (window.requestsLineChart && typeof window.requestsLineChart.destroy === 'function') window.requestsLineChart.destroy();
            const lineCtx = document.getElementById('requestsLineChart').getContext('2d');
            window.requestsLineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: data.dailyStats.map(d => d.date),
                    datasets: [{
                        label: 'Заявки',
                        data: data.dailyStats.map(d => d.count),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    scales: {
                        x: { title: { display: true, text: 'Дата' } },
                        y: { title: { display: true, text: 'Количество' }, beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Таблица топ-5 дней
            const tableBody = document.getElementById('top-days-table');
            tableBody.innerHTML = '';
            if (data.topDays.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="2" class="text-center">Нет данных</td></tr>';
            } else {
                data.topDays.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.date}</td><td>${row.count}</td>`;
                    tableBody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            console.error('Ошибка при запросе аналитики:', err);
            alert('Ошибка при загрузке аналитики. Подробности в консоли.');
            document.getElementById('total-requests').textContent = 'Ошибка';
            document.getElementById('requests-week').textContent = 'Ошибка';
            document.getElementById('requests-month').textContent = 'Ошибка';
            document.getElementById('top-days-table').innerHTML = '<tr><td colspan="2" class="text-danger text-center">Ошибка загрузки данных</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('analytics/requests.js loaded');
    // Подгружаем категории
    fetchCategories().then(categories => {
        const catSelect = document.getElementById('category');
        categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            catSelect.appendChild(opt);
        });
    });

    // При изменении категории — подгружаем услуги
    document.getElementById('category').addEventListener('change', function () {
        const catId = this.value;
        const serviceSelect = document.getElementById('service');
        serviceSelect.innerHTML = '<option value="">Все</option>';
        serviceSelect.disabled = true;
        if (catId) {
            fetchServices(catId).then(services => {
                services.forEach(serv => {
                    const opt = document.createElement('option');
                    opt.value = serv.id;
                    opt.textContent = serv.name;
                    serviceSelect.appendChild(opt);
                });
                serviceSelect.disabled = false;
            });
        }
    });

    // Любое изменение фильтров — обновляем аналитику
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);

    // Первая отрисовка
    updateAnalytics();
}); 