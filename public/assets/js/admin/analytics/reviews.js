// JS для аналитики по отзывам

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
    let url = 'reviews.php?action=stats';
    const query = buildQuery(filters);
    if (query) url += '&' + query;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Метрики
            document.getElementById('total-reviews').textContent = data.total;
            document.getElementById('reviews-period').textContent = data.countPeriod;
            document.getElementById('avg-length').textContent = data.avgLength;

            // График по неделям
            if (window.reviewsLineChart && typeof window.reviewsLineChart.destroy === 'function') window.reviewsLineChart.destroy();
            const chartEl = document.getElementById('reviewsLineChart');
            if (chartEl) {
                window.reviewsLineChart = new Chart(chartEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.weeklyStats.map(w => w.week_start),
                        datasets: [{
                            label: 'Отзывов',
                            data: data.weeklyStats.map(w => w.count),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            x: { title: { display: true, text: 'Неделя' } },
                            y: { beginAtZero: true, title: { display: true, text: 'Отзывов' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // Таблица последних отзывов
            const tableBody = document.getElementById('last-reviews-table');
            tableBody.innerHTML = '';
            if (!data.lastReviews || data.lastReviews.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Нет данных</td></tr>';
            } else {
                data.lastReviews.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.author}</td><td class="review-text" title="${row.text}">${row.text.length > 60 ? row.text.slice(0, 60) + '…' : row.text}</td><td>${row.created_at}</td>`;
                    tableBody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            document.getElementById('total-reviews').textContent = 'Ошибка';
            document.getElementById('reviews-period').textContent = 'Ошибка';
            document.getElementById('avg-length').textContent = 'Ошибка';
            document.getElementById('last-reviews-table').innerHTML = '<tr><td colspan="3" class="text-danger text-center">Ошибка загрузки данных</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('filters-form').addEventListener('change', updateAnalytics);
    updateAnalytics();
}); 