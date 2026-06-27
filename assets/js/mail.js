(function () {
    var data = window.MegaStatsMail && window.MegaStatsMail.history ? window.MegaStatsMail.history : [];
    var canvas = document.getElementById('mailChartScore');
    if (!canvas || typeof Chart === 'undefined' || !data.length) {
        return;
    }

    var labels = data.map(function (d) { return d.date; });
    var scores = data.map(function (d) { return d.score; });
    var rbl = data.map(function (d) { return d.rbl_listed; });

    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
    var text = dark ? '#adb5bd' : '#6c757d';

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Score',
                    data: scores,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: 'RBL',
                    data: rbl,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.25,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: text } } },
            scales: {
                x: { ticks: { color: text, maxTicksLimit: 8 }, grid: { color: grid } },
                y: { min: 0, max: 100, ticks: { color: text }, grid: { color: grid } },
                y1: { position: 'right', min: 0, ticks: { color: text }, grid: { drawOnChartArea: false } }
            }
        }
    });
})();
