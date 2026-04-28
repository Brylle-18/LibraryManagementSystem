function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const el = document.getElementById('topbar-time');
    if (el) el.innerHTML = `${timeStr}<br>${dateStr}`;
}
updateClock();
setInterval(updateClock, 1000);

const activePage = document.body.dataset.activePage || 'dashboard';
const activeTab = document.body.dataset.activeTab || 'borrowed';

document.querySelectorAll('.menu ul li a[data-page]').forEach(link => {
    link.classList.toggle('active', link.dataset.page === activePage);
});

document.querySelectorAll('.page').forEach(page => {
    page.classList.toggle('active', page.id === 'page-' + activePage);
});

document.querySelectorAll('.tab[data-tab]').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.tab === activeTab);
});

document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.toggle('active', content.id === 'tab-' + activeTab);
});

window.addEventListener('load', () => {
    const ctx = document.getElementById('borrowChart');
    if (!ctx) return;

    const chartData = window.borrowChartData || { borrowed: 0, returned: 0 };

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Total Borrowed Books', 'Total Returned Books'],
            datasets: [{
                data: [chartData.borrowed, chartData.returned],
                backgroundColor: ['#111111', '#888888'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => ` ${context.label}: ${context.parsed}`
                    },
                }
            }
        }
    });
});
