import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. AUTO-SUBMIT FILTROS
    // Al cambiar el mes o el gestor, recargamos la página
    const filterForm = document.getElementById('filtrosDash');
    if (filterForm) {
        const inputs = filterForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', () => filterForm.submit());
        });
    }

    // 2. GRÁFICA DE PAGOS (Línea)
    const ctxLine = document.getElementById('linePagos');
    if (ctxLine) {
        // Leemos los datos del atributo data-chart (inyectado desde Blade)
        const payload = JSON.parse(ctxLine.dataset.chart || '{}');
        const labels = payload.labels || [];
        const data   = payload.data || [];

        const brandColor = '#6F2661'; // Morado marca

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Recaudo (S/)',
                    data: data,
                    borderColor: brandColor,
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(111, 38, 97, 0.2)'); // Brand con opacidad
                        gradient.addColorStop(1, 'rgba(111, 38, 97, 0)');
                        return gradient;
                    },
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: brandColor,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 // Curvas suaves
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => 'S/ ' + Number(ctx.parsed.y).toLocaleString('es-PE', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    },
                    y: {
                        border: { display: false }, // Sin línea de eje Y
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: (v) => 'S/ ' + (v >= 1000 ? (v/1000)+'k' : v)
                        }
                    }
                }
            }
        });
    }
});