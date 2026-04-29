function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const el = document.getElementById('topbar-time');
    if (el) el.innerHTML = `${timeStr}<br>${dateStr}`;
}
updateClock();
setInterval(updateClock, 1000);

// URL-driven page state: read ?page= and ?tab= from query string to set active states
const dashboardParams = new URLSearchParams(window.location.search);
const activePageFromQuery = dashboardParams.get('page');

if (activePageFromQuery) {
    document.querySelectorAll('.menu ul li a').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const targetNav = document.querySelector(`.menu ul li a[data-page="${activePageFromQuery}"]`);
    const targetPage = document.getElementById('page-' + activePageFromQuery);
    if (targetNav && targetPage) {
        targetNav.classList.add('active');
        targetPage.classList.add('active');
    }
}

document.querySelectorAll('.menu ul li a[data-page]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const page = link.dataset.page;

        document.querySelectorAll('.menu ul li a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');

        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById('page-' + page)?.classList.add('active');
    });
});

document.querySelectorAll('.tab[data-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab[data-tab]').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const target = tab.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + target)?.classList.add('active');
    });
});

window.addEventListener('load', () => {
    const ctx = document.getElementById('borrowChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const borrowedTotal = Number(ctx.dataset.borrowed || 0);
    const returnedTotal = Number(ctx.dataset.returned || 0);
    const chartValues = (borrowedTotal + returnedTotal) > 0 ? [borrowedTotal, returnedTotal] : [1, 0];

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Total Borrowed Books', 'Total Returned Books'],
            datasets: [{
                data: chartValues,
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
                        label: item => ` ${item.label}: ${item.parsed}`
                    }
                }
            }
        }
    });
});
                    }
>>>>>>> 76b3dfd (Implement RBAC, student dashboard, admin CRUD, and borrow/return transactions)
                }
            }
        }
    });
});
