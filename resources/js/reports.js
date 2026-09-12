import Chart from 'chart.js/auto';

export function reportsPage(payload) {
    return {
        init() {
            this.draw('sportChart', 'bar', payload.by_sport, '#1d4ed8');
            this.draw('dayChart', 'line', payload.by_day, '#84cc16');
            this.draw('monthChart', 'bar', payload.revenue_by_month, '#0b1d36');
            this.draw('payChart', 'doughnut', payload.payment_dist, ['#16a34a', '#f97316', '#eab308']);
        },
        draw(id, type, data, color) {
            const el = document.getElementById(id);
            if (!el) return;
            const labels = Object.keys(data || {});
            const values = Object.values(data || {});
            new Chart(el, {
                type,
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: Array.isArray(color) ? color : color,
                        borderColor: Array.isArray(color) ? '#fff' : color,
                        borderWidth: type === 'line' ? 3 : 0,
                        fill: type === 'line',
                        tension: 0.35,
                    }],
                },
                options: {
                    plugins: { legend: { display: type === 'doughnut' } },
                    scales: type === 'doughnut' ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    maintainAspectRatio: false,
                },
            });
        },
    };
}
