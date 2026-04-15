function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const el = document.getElementById('topbar-time');
    if (el) el.innerHTML = `${timeStr}<br>${dateStr}`;
}
updateClock();
setInterval(updateClock, 1000);

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
    if (!ctx) return;

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Total Borrowed Books', 'Total Returned Books'],
            datasets: [{
                data: [60, 40],
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
                        label: ctx => ` ${ctx.label}: ${ctx.parsed}%`
                    }
                }
            }
        }
    });
});


function formatNum(n) {
    return String(n).padStart(4, '0');
}

function renderPersonList(containerId, items, type) {
    const container = document.getElementById(containerId);
    if (!container || !items.length) return;
    container.innerHTML = items.map(item => `
        <div class="person-row">
            <div class="person-icon"><i class="fa-solid fa-${type === 'admin' ? 'user-shield' : 'user'}"></i></div>
            <div class="person-details">
                <div class="person-name">${item.name}</div>
                <div class="person-sub">${item.sub}</div>
                ${type === 'admin' ? `<span><span class="status-dot"></span><small>Active</small></span>` : ''}
            </div>
            <i class="fa-solid fa-rotate person-action"></i>
        </div>
    `).join('');
}

function renderTable(wrapperId, columns, rows) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper || !rows.length) return;
    const thead = columns.map(c => `<th>${c}</th>`).join('');
    const tbody = rows.map(row =>
        `<tr>${columns.map((_, i) => `<td>${row[i] ?? ''}</td>`).join('')}<td><i class="fa-solid fa-clipboard-list action-icon"></i></td></tr>`
    ).join('');
    wrapper.innerHTML = `
        <table class="data-table">
            <thead><tr>${thead}<th>Action</th></tr></thead>
            <tbody>${tbody}</tbody>
        </table>`;
}