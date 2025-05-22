// JS для аналитики сезонности и трендов 

let currentSeason = 'winter';

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
    let url = 'trends.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Топ-3 месяца
            const topMonthsList = document.getElementById('top-months-list');
            topMonthsList.innerHTML = '';
            if (!data.topMonths || data.topMonths.length === 0) {
                topMonthsList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                data.topMonths.forEach(m => {
                    const ym = m.ym.split('-');
                    const monthName = new Date(m.ym + '-01').toLocaleString('ru-RU', {month: 'long', year: 'numeric'});
                    topMonthsList.innerHTML += `<li>${monthName.charAt(0).toUpperCase() + monthName.slice(1)} <span class="badge bg-primary ms-2">${m.count}</span></li>`;
                });
            }

            // Сезонные табы
            const seasonTabs = document.getElementById('season-tabs');
            seasonTabs.innerHTML = '';
            Object.entries(data.seasonNames).forEach(([key, name]) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm ' + (currentSeason === key ? 'btn-primary' : 'btn-outline-primary') + ' me-1';
                btn.textContent = name;
                btn.onclick = function() {
                    currentSeason = key;
                    updateAnalytics();
                };
                seasonTabs.appendChild(btn);
            });

            // Топ-5 услуг по выбранному сезону (список)
            const topServicesList = document.getElementById('top-services-list');
            topServicesList.innerHTML = '';
            const topServices = data.topServicesBySeason[currentSeason] || [];
            if (topServices.length === 0) {
                topServicesList.innerHTML = '<li class="text-muted">Нет данных</li>';
            } else {
                topServices.forEach(s => {
                    topServicesList.innerHTML += `<li>${s.name} <span class="badge bg-success ms-2">${s.cnt}</span></li>`;
                });
            }

            // Линейный график: заявки по месяцам
            if (window.requestsLineChart && typeof window.requestsLineChart.destroy === 'function') window.requestsLineChart.destroy();
            const lineEl = document.getElementById('requestsLineChart');
            if (lineEl) {
                window.requestsLineChart = new Chart(lineEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.monthlyStats.map(m => m.ym),
                        datasets: [{
                            label: 'Заявок',
                            data: data.monthlyStats.map(m => m.count),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            x: { title: { display: true, text: 'Месяц' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Заявок' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // Столбчатая диаграмма: топ-5 услуг по выбранному сезону
            if (window.seasonBarChart && typeof window.seasonBarChart.destroy === 'function') window.seasonBarChart.destroy();
            const barEl = document.getElementById('seasonBarChart');
            if (barEl) {
                window.seasonBarChart = new Chart(barEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: topServices.map(s => s.name),
                        datasets: [{
                            label: 'Заявок',
                            data: topServices.map(s => s.cnt),
                            backgroundColor: '#198754',
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { title: { display: true, text: 'Услуга' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Заявок' } }
                        }
                    }
                });
            }
        })
        .catch(err => {
            document.getElementById('top-months-list').innerHTML = '<li class="text-danger">Ошибка</li>';
            document.getElementById('top-services-list').innerHTML = '<li class="text-danger">Ошибка</li>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);
    updateAnalytics();
}); 