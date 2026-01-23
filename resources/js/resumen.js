import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {

    // 1. SELECTOR DE MES
    const mesPicker = document.getElementById('mesPicker');
    if (mesPicker) {
        mesPicker.addEventListener('change', (e) => {
            const ym = e.target.value || '';
            const url = new URL(window.location.href);
            url.searchParams.set('mes', ym);
            window.location.assign(url.toString());
        });
    }

    // 2. GRÁFICA DE PAGOS
    const chartEl = document.getElementById('chartPagos');
    if (chartEl) {
        const payload = (() => {
            try { return JSON.parse(chartEl.dataset.chart || '{}'); } catch (_) { return {}; }
        })();

        const ctx = chartEl.getContext('2d');
        const brandColor = '#6F2661'; 

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: payload.labels || [],
                datasets: [{
                    label: 'Recaudo (S/)',
                    data: payload.data || [],
                    backgroundColor: 'rgba(111, 38, 97, 0.15)',
                    borderColor: brandColor,
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(111, 38, 97, 0.35)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => 'S/ ' + Number(ctx.parsed.y ?? 0).toLocaleString('es-PE', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: { 
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#f1f5f9' },
                        ticks: { 
                            callback: (v) => 'S/ ' + (v >= 1000 ? (v/1000)+'k' : v),
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    }
});