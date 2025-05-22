// JS для аналитики по категориям

function getFilters() {
    return {
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value
    };
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
    let url = 'categories.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Метрики: услуги по категориям
            const servicesList = document.getElementById('services-by-category-list');
            servicesList.innerHTML = '';
            if (data.servicesByCategory.length === 0) {
                servicesList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.servicesByCategory.forEach(row => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${row.name}</span>: <span class="text-primary">${row.services_count}</span>`;
                    servicesList.appendChild(li);
                });
            }
            // Метрики: заявки по категориям
            const requestsList = document.getElementById('requests-by-category-list');
            requestsList.innerHTML = '';
            if (data.requestsByCategory.length === 0) {
                requestsList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.requestsByCategory.forEach(row => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${row.name}</span>: <span class="text-primary">${row.requests_count}</span>`;
                    requestsList.appendChild(li);
                });
            }

            // Столбчатая диаграмма (заявки по категориям)
            if (window.requestsBarChart && typeof window.requestsBarChart.destroy === 'function') window.requestsBarChart.destroy();
            const barCtx = document.getElementById('requestsBarChart').getContext('2d');
            window.requestsBarChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: data.barChart.map(c => c.name),
                    datasets: [{
                        label: 'Заявки',
                        data: data.barChart.map(c => c.requests_count),
                        backgroundColor: '#0d6efd',
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Категория' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Количество заявок' } }
                    }
                }
            });

            // Круговая диаграмма (распределение услуг по категориям)
            if (window.servicesPieChart && typeof window.servicesPieChart.destroy === 'function') window.servicesPieChart.destroy();
            const pieCtx = document.getElementById('servicesPieChart').getContext('2d');
            window.servicesPieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: data.pieChart.map(c => c.name + ' (' + c.services_count + ')'),
                    datasets: [{
                        data: data.pieChart.map(c => c.services_count),
                        backgroundColor: [
                            '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#e83e8c', '#adb5bd'
                        ]
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Таблица: категории без услуг или с <5 заявок
            const tableBody = document.getElementById('categories-few-requests-table');
            tableBody.innerHTML = '';
            if (data.fewRequests.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Нет данных</td></tr>';
            } else {
                data.fewRequests.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.name}</td><td>${row.services_count}</td><td>${row.requests_count}</td>`;
                    tableBody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            document.getElementById('services-by-category-list').innerHTML = '<li class="text-danger">Ошибка</li>';
            document.getElementById('requests-by-category-list').innerHTML = '<li class="text-danger">Ошибка</li>';
            document.getElementById('categories-few-requests-table').innerHTML = '<tr><td colspan="3" class="text-danger text-center">Ошибка загрузки данных</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);
    updateAnalytics();
});

// === PDF EXPORT ===
document.getElementById('download-pdf').addEventListener('click', function () {
    // Метрики: услуги по категориям
    const servicesByCategory = [];
    document.querySelectorAll('#services-by-category-list li').forEach(li => {
        const parts = li.textContent.split(':');
        if (parts.length === 2) servicesByCategory.push({ name: parts[0].trim(), services_count: parts[1].replace(/[^\d]/g, '').trim() });
    });
    // Метрики: заявки по категориям
    const requestsByCategory = [];
    document.querySelectorAll('#requests-by-category-list li').forEach(li => {
        const parts = li.textContent.split(':');
        if (parts.length === 2) requestsByCategory.push({ name: parts[0].trim(), requests_count: parts[1].replace(/[^\d]/g, '').trim() });
    });
    // Графики
    let barImg = '';
    let pieImg = '';
    if (window.requestsBarChart) {
        barImg = window.requestsBarChart.toBase64Image();
    }
    if (window.servicesPieChart) {
        pieImg = window.servicesPieChart.toBase64Image();
    }
    // Таблица: категории с малым количеством заявок
    const fewRequests = [];
    document.querySelectorAll('#categories-few-requests-table tr').forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (tds.length === 3 && !tds[0].classList.contains('text-center')) {
            fewRequests.push({ name: tds[0].textContent, services_count: tds[1].textContent, requests_count: tds[2].textContent });
        }
    });
    // Период
    const date_from = document.getElementById('date-from').value;
    const date_to = document.getElementById('date-to').value;
    // Формируем данные
    const data = {
        servicesByCategory,
        requestsByCategory,
        barImg,
        pieImg,
        fewRequests,
        date_from,
        date_to
    };
    fetch('/admin/pdf/categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => {
        if (!res.ok) throw new Error('Ошибка генерации PDF');
        return res.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'categories_analytics_report.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => {
        alert('Ошибка при генерации PDF: ' + err.message);
    });
}); 