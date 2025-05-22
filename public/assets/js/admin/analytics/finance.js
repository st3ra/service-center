// JS для финансовой аналитики

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
    let url = 'finance.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Метрики
            document.getElementById('total-revenue').textContent = (data.totalRevenue ? data.totalRevenue : 0).toLocaleString('ru-RU', {style: 'currency', currency: 'RUB', maximumFractionDigits: 0});
            document.getElementById('avg-check').textContent = (data.avgCheck ? data.avgCheck : 0).toLocaleString('ru-RU', {style: 'currency', currency: 'RUB', maximumFractionDigits: 0});

            // Линейный график: выручка по месяцам
            if (window.revenueLineChart && typeof window.revenueLineChart.destroy === 'function') window.revenueLineChart.destroy();
            const lineEl = document.getElementById('revenueLineChart');
            if (lineEl) {
                window.revenueLineChart = new Chart(lineEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.monthlyRevenue.map(m => m.ym),
                        datasets: [{
                            label: 'Выручка',
                            data: data.monthlyRevenue.map(m => m.revenue),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            x: { title: { display: true, text: 'Месяц' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Выручка (₽)' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // Круговая диаграмма: доля выручки по категориям
            if (window.categoryPieChart && typeof window.categoryPieChart.destroy === 'function') window.categoryPieChart.destroy();
            const pieEl = document.getElementById('categoryPieChart');
            if (pieEl) {
                window.categoryPieChart = new Chart(pieEl.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: data.categoryRevenue.map(c => c.category + ' (' + parseInt(c.revenue).toLocaleString('ru-RU') + '₽)'),
                        datasets: [{
                            data: data.categoryRevenue.map(c => c.revenue),
                            backgroundColor: [
                                '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#adb5bd', '#e83e8c'
                            ]
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        })
        .catch(err => {
            document.getElementById('total-revenue').textContent = 'Ошибка';
            document.getElementById('avg-check').textContent = 'Ошибка';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);
    updateAnalytics();
});

// === PDF EXPORT ===
document.getElementById('download-pdf').addEventListener('click', function () {
    // Метрики
    const totalRevenue = document.getElementById('total-revenue').textContent;
    const avgCheck = document.getElementById('avg-check').textContent;
    // Графики
    let lineImg = '';
    let pieImg = '';
    if (window.revenueLineChart) {
        lineImg = window.revenueLineChart.toBase64Image();
    }
    if (window.categoryPieChart) {
        pieImg = window.categoryPieChart.toBase64Image();
    }
    // Период
    const date_from = document.getElementById('date-from').value;
    const date_to = document.getElementById('date-to').value;
    // Формируем данные
    const data = {
        totalRevenue,
        avgCheck,
        lineImg,
        pieImg,
        date_from,
        date_to
    };
    fetch('/admin/pdf/finance.php', {
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
        a.download = 'finance_analytics_report.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => {
        alert('Ошибка при генерации PDF: ' + err.message);
    });
}); 