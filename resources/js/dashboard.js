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
                        backgroundColor: '#1a52f4',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.55,
                    },
                    {
                        label: 'Pending',
                        data: pending,
                        backgroundColor: '#e2e8f0',
                        borderRadius: 8,
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
                        labels: { boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true, font: { size: 11 } },
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
                    y: { grid: { color: '#f1f5f9' }, border: { dash: [4, 4] }, ticks: { font: { size: 11 }, color: '#94a3b8' }, beginAtZero: true },
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
