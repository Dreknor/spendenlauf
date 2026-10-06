import {
    Chart, BarController, BarElement, DoughnutController, ArcElement, LineController, LineElement, PointElement,
    CategoryScale, LinearScale, Legend, Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, DoughnutController, ArcElement, LineController, LineElement, PointElement,
    CategoryScale, LinearScale, Legend, Tooltip);

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.color = '#475569';

const PALETTE = ['#cc224d', '#002561', '#2c519f', '#e2668a', '#7f9dd6', '#a91a40', '#0f766e', '#f59e0b', '#64748b', '#8b5cf6'];

const euro = (v) => (v / 100).toLocaleString('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

/**
 * Rendert alle <canvas data-chart='{...}'> der Seite.
 * Format: { type, labels, datasets: [{label, data}], euro: bool, horizontal: bool, stacked: bool }
 */
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const cfg = JSON.parse(canvas.dataset.chart);
        const isPie = cfg.type === 'doughnut';
        const datasets = cfg.datasets.map((ds, i) => ({
            ...ds,
            backgroundColor: isPie ? PALETTE : (ds.color || PALETTE[i % PALETTE.length]),
            borderColor: isPie ? '#fff' : (ds.color || PALETTE[i % PALETTE.length]),
            borderRadius: cfg.type === 'bar' ? 4 : 0,
            maxBarThickness: 48,
        }));

        const format = (v) => (cfg.euro ? euro(v) : Number(v).toLocaleString('de-DE'));

        new Chart(canvas, {
            type: cfg.type,
            data: { labels: cfg.labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: cfg.horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: isPie || datasets.length > 1, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label || ctx.label}: ${format(ctx.raw)}`,
                        },
                    },
                },
                scales: isPie ? {} : {
                    x: { stacked: !!cfg.stacked, grid: { display: false }, ticks: cfg.horizontal ? { callback: (v) => format(v) } : {} },
                    y: { stacked: !!cfg.stacked, beginAtZero: true, ticks: cfg.horizontal ? {} : { callback: (v) => format(v) } },
                },
            },
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCharts);
} else {
    renderCharts();
}
