// JS для аналитики по услугам 

function fetchCategories() {
    return fetch('services.php?action=categories')
        .then(res => res.json());
}

function getSelectedCategories() {
    // Гарантируем, что всегда возвращается массив
    const arr = Array.from(document.querySelectorAll('#category-checkboxes input[type=checkbox]:checked'))
        .map(cb => cb.value);
    return arr;
}

function getFilters() {
    return {
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        category: getSelectedCategories()
    };
}

function buildQuery(params) {
    const esc = encodeURIComponent;
    let parts = [];
    for (const key in params) {
        if (Array.isArray(params[key])) {
            params[key].forEach(val => {
                parts.push(esc(key + '[]') + '=' + esc(val));
            });
        } else if (params[key]) {
            parts.push(esc(key) + '=' + esc(params[key]));
        }
    }
    return parts.join('&');
}

function updateAnalytics() {
    const filters = getFilters();
    let url = 'services.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Метрики
            // Топ-5 популярных услуг (список)
            const topList = document.getElementById('top-services-list');
            topList.innerHTML = '';
            if (data.topServices.length === 0) {
                topList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.topServices.forEach(s => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${s.name}</span> <span class="badge bg-secondary ms-2">${s.requests_count}</span>`;
                    topList.appendChild(li);
                });
            }
            // Средняя цена по категориям (список)
            const avgList = document.getElementById('avg-price-list');
            avgList.innerHTML = '';
            if (data.avgPrices.length === 0) {
                avgList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.avgPrices.forEach(p => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${p.category}</span>: <span class="text-primary">${p.avg_price}₽</span>`;
                    avgList.appendChild(li);
                });
            }
            document.getElementById('revenue-completed').textContent = data.revenue.toLocaleString('ru-RU', {style: 'currency', currency: 'RUB', maximumFractionDigits: 0});
            document.getElementById('services-no-requests').textContent = data.servicesNoRequests.length;

            // Столбчатая диаграмма (топ-10 услуг)
            if (window.servicesBarChart && typeof window.servicesBarChart.destroy === 'function') window.servicesBarChart.destroy();
            const barCtx = document.getElementById('servicesBarChart').getContext('2d');
            window.servicesBarChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: data.barChart.map(s => s.name),
                    datasets: [{
                        label: 'Заявки',
                        data: data.barChart.map(s => s.requests_count),
                        backgroundColor: '#0d6efd',
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true, title: { display: true, text: 'Количество заявок' } },
                        y: { title: { display: true, text: 'Услуга' } }
                    }
                }
            });

            // Круговая диаграмма (доля выручки по категориям)
            if (window.revenuePieChart && typeof window.revenuePieChart.destroy === 'function') window.revenuePieChart.destroy();
            const pieCtx = document.getElementById('revenuePieChart').getContext('2d');
            window.revenuePieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: data.pieChart.map(c => c.category + ' (' + (c.revenue ? c.revenue : 0) + '₽)'),
                    datasets: [{
                        data: data.pieChart.map(c => c.revenue),
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

            // Таблица услуг без заявок
            const tableBody = document.getElementById('services-no-requests-table');
            tableBody.innerHTML = '';
            if (data.servicesNoRequests.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Нет данных</td></tr>';
            } else {
                data.servicesNoRequests.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.name}</td><td>${row.category}</td><td>${row.price}₽</td>`;
                    tableBody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            document.getElementById('top-services').textContent = 'Ошибка';
            document.getElementById('revenue-completed').textContent = 'Ошибка';
            document.getElementById('services-no-requests').textContent = 'Ошибка';
            document.getElementById('avg-price-by-category').textContent = 'Ошибка';
            document.getElementById('services-no-requests-table').innerHTML = '<tr><td colspan="3" class="text-danger text-center">Ошибка загрузки данных</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    // Подгружаем категории как чекбоксы
    fetchCategories().then(categories => {
        const boxDiv = document.getElementById('category-checkboxes');
        boxDiv.innerHTML = '';
        categories.forEach(cat => {
            const id = 'cat_' + cat.id;
            const div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = `<input class="form-check-input" type="checkbox" value="${cat.id}" id="${id}">
                <label class="form-check-label" for="${id}">${cat.name}</label>`;
            boxDiv.appendChild(div);
        });
    });

    // Кнопка 'Применить' — обновить аналитику
    document.getElementById('apply-categories').addEventListener('click', function () {
        updateAnalytics();
        // Закрыть dropdown
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(document.getElementById('categoryDropdown'));
        dropdown.hide();
    });

    // Кнопка 'Сбросить' — снять все чекбоксы и обновить аналитику
    document.getElementById('reset-categories').addEventListener('click', function () {
        document.querySelectorAll('#category-checkboxes input[type=checkbox]').forEach(cb => cb.checked = false);
        updateAnalytics();
        // Закрыть dropdown
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(document.getElementById('categoryDropdown'));
        dropdown.hide();
    });

    // Любое изменение других фильтров — обновляем аналитику
    document.getElementById('filters-form').addEventListener('change', function(e) {
        if (!e.target.closest('#category-checkboxes')) {
            updateAnalytics();
        }
    });

    // Не закрывать dropdown при клике по чекбоксу
    document.getElementById('category-dropdown-menu').addEventListener('click', function(e) {
        if (e.target.matches('input[type=checkbox]')) {
            e.stopPropagation();
        }
    });

    // Первая отрисовка
    updateAnalytics();
}); 