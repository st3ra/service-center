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

// === PDF EXPORT ===
document.getElementById('download-pdf').addEventListener('click', function () {
    // Топ-3 месяца
    const topMonths = [];
    document.querySelectorAll('#top-months-list li').forEach(li => {
        if (!li.classList.contains('text-muted') && !li.classList.contains('text-danger')) {
            topMonths.push(li.textContent.trim());
        }
    });
    // Топ-5 услуг по выбранному сезону
    const topServices = [];
    document.querySelectorAll('#top-services-list li').forEach(li => {
        if (!li.classList.contains('text-muted') && !li.classList.contains('text-danger')) {
            topServices.push(li.textContent.trim());
        }
    });
    // Графики
    let lineImg = '';
    let barImg = '';
    if (window.requestsLineChart) {
        lineImg = window.requestsLineChart.toBase64Image();
    }
    if (window.seasonBarChart) {
        barImg = window.seasonBarChart.toBase64Image();
    }
    // Период
    const date_from = document.getElementById('date-from').value;
    const date_to = document.getElementById('date-to').value;
    // Сезон
    const seasonTabs = document.getElementById('season-tabs');
    let selectedSeason = '';
    seasonTabs.querySelectorAll('button').forEach(btn => {
        if (btn.classList.contains('btn-primary')) selectedSeason = btn.textContent;
    });
    // Формируем данные
    const data = {
        topMonths,
        topServices,
        lineImg,
        barImg,
        date_from,
        date_to,
        selectedSeason
    };
    fetch('/admin/pdf/trends.php', {
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
        a.download = 'trends_analytics_report.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => {
        alert('Ошибка при генерации PDF: ' + err.message);
    });
});

// Вывод выбранного сезона под табами
function showSelectedSeason() {
    const seasonTabs = document.getElementById('season-tabs');
    let selectedSeason = '';
    seasonTabs.querySelectorAll('button').forEach(btn => {
        if (btn.classList.contains('btn-primary')) selectedSeason = btn.textContent;
    });
    let el = document.getElementById('selected-season');
    if (!el) {
        el = document.createElement('div');
        el.id = 'selected-season';
        el.className = 'mb-2 text-center fw-bold';
        seasonTabs.parentNode.insertBefore(el, seasonTabs.nextSibling);
    }
    el.textContent = 'Выбранный сезон: ' + selectedSeason;
}

// Вызов showSelectedSeason при обновлении табов
const origUpdateAnalytics = updateAnalytics;
updateAnalytics = function() {
    origUpdateAnalytics.apply(this, arguments);
    setTimeout(showSelectedSeason, 0);
}; 