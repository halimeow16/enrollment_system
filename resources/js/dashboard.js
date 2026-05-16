document.addEventListener('DOMContentLoaded', () => {
        window.lucide?.createIcons();

        if (!window.appData?.chartData || !document.getElementById('enrollmentChart')) {
            return;
        }

        const semesterData = window.appData.chartData.semester;
        const yearData     = window.appData.chartData.year;

        const ctx = document.getElementById('enrollmentChart').getContext('2d');

        const chartConfig = (labels, enrolled, pending) => ({
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Enrolled',
                        data: enrolled,
                        backgroundColor: '#2563eb',
                        borderRadius: 10,
                        borderSkipped: false,
                        barPercentage: 0.55,
                    },
                    {
                        label: 'Pending',
                        data: pending,
                        backgroundColor: 'rgba(255, 255, 255, 0.18)',
                        borderRadius: 10,
                        borderSkipped: false,
                        barPercentage: 0.55,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true, font: { size: 11 }, color: '#cbd5e1' },
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 10,
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(148, 163, 184, 0.16)' }, border: { dash: [4, 4], color: 'rgba(148, 163, 184, 0.25)' }, ticks: { font: { size: 11 }, color: '#94a3b8' }, beginAtZero: true },
                },
            },
        });

        let chart = new Chart(ctx, chartConfig(
            semesterData.labels,
            semesterData.enrolled,
            semesterData.pending
        ));

        window.updateChart = (period) => {
            const d = period === 'year' ? yearData : semesterData;
            chart.data.labels           = d.labels;
            chart.data.datasets[0].data = d.enrolled;
            chart.data.datasets[1].data = d.pending;
            chart.update();
        };
    });
