(function () {
    'use strict';

    var cfg = window.MegaStatsCharts;
    if (!cfg || typeof Chart === 'undefined') {
        return;
    }

    var charts = {};
    var lastPayload = cfg.initial || {};
    var currentRange = cfg.range || '1d';
    var customFrom = cfg.fromTs || null;
    var customTo = cfg.toTs || null;
    var palette = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14', '#198754', '#20c997', '#dc3545'];

    function themeColors() {
        var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            text: dark ? '#dee2e6' : '#495057',
            grid: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)',
        };
    }

    function baseOptions(title) {
        var colors = themeColors();
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: colors.text, boxWidth: 10 } },
                title: { display: true, text: title, color: colors.text, font: { size: 13 } },
            },
            scales: {
                x: { ticks: { color: colors.text, maxTicksLimit: 8 }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid }, beginAtZero: true },
            },
        };
    }

    function lineDataset(label, data, color) {
        return {
            label: label,
            data: data,
            borderColor: color,
            backgroundColor: color + '33',
            tension: 0.25,
            fill: true,
            pointRadius: 0,
            spanGaps: true,
        };
    }

    function mountChart(id, title, datasets, labels) {
        var canvas = document.getElementById(id);
        if (!canvas) {
            return;
        }

        if (charts[id]) {
            charts[id].destroy();
        }

        charts[id] = new Chart(canvas, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: baseOptions(title),
        });
    }

    function render(payload) {
        lastPayload = payload || {};
        var labels = lastPayload.labels || [];
        var series = lastPayload.series || {};

        mountChart('chartCpu', 'CPU %', [lineDataset('CPU', series.cpu || [], palette[0])], labels);
        mountChart('chartMemory', 'RAM %', [lineDataset('RAM used', series.mem_used_pct || [], palette[1])], labels);
        mountChart('chartLoad', 'Load (1 min)', [lineDataset('Load 1m', series.load1 || [], palette[2])], labels);
        mountChart('chartDisk', 'Disk max %', [lineDataset('Max partition', series.disk_max_pct || [], palette[3])], labels);
        mountChart('chartNetwork', 'Network today (MB)', [lineDataset('Today MB', series.network_today_mb || [], palette[4])], labels);
        mountChart('chartUsers', 'Clients connectés (IP)', [lineDataset('IP uniques', series.connected_users || [], palette[5])], labels);

        var diskDatasets = (lastPayload.disk_series || []).map(function (item, index) {
            return lineDataset(item.label, item.data || [], palette[index % palette.length]);
        });

        mountChart('chartDiskMounts', 'Disk by mount %', diskDatasets, labels);

        var badge = document.getElementById('chartsUpdated');
        if (badge) {
            badge.textContent = 'Maj ' + new Date((lastPayload.updated_at || Date.now()) * 1000).toLocaleTimeString();
        }
    }

    function buildApiUrl() {
        var apiUrl = cfg.apiUrl;
        if (apiUrl.indexOf('cpsess') === -1 && window.location.pathname.indexOf('cpsess') !== -1) {
            apiUrl = window.location.pathname + '?api=metrics';
        }

        var params = new URLSearchParams();
        params.set('range', currentRange);
        if (currentRange === 'custom') {
            if (customFrom) {
                params.set('from', String(customFrom));
            }
            if (customTo) {
                params.set('to', String(customTo));
            }
        }

        return apiUrl + (apiUrl.indexOf('?') >= 0 ? '&' : '?') + params.toString();
    }

    function setActiveRangeButton(range) {
        document.querySelectorAll('#chartRangeGroup [data-range]').forEach(function (btn) {
            var active = btn.getAttribute('data-range') === range;
            btn.classList.toggle('btn-secondary', active);
            btn.classList.toggle('btn-outline-secondary', !active);
        });

        var customBox = document.getElementById('chartCustomRange');
        if (customBox) {
            customBox.classList.toggle('d-none', range !== 'custom');
        }
    }

    function refresh() {
        if (!cfg.apiUrl) {
            return;
        }

        fetch(buildApiUrl(), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(render)
            .catch(function () {
                /* silent */
            });
    }

    function parseDatetimeLocal(value) {
        if (!value) {
            return null;
        }
        var ts = Date.parse(value);
        return Number.isNaN(ts) ? null : Math.floor(ts / 1000);
    }

    render(cfg.initial || {});
    setActiveRangeButton(currentRange);

    document.querySelectorAll('#chartRangeGroup [data-range]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentRange = btn.getAttribute('data-range') || '1d';
            setActiveRangeButton(currentRange);
            if (currentRange !== 'custom') {
                refresh();
            }
        });
    });

    var customApply = document.getElementById('chartCustomApply');
    if (customApply) {
        customApply.addEventListener('click', function () {
            customFrom = parseDatetimeLocal(document.getElementById('chartFrom')?.value);
            customTo = parseDatetimeLocal(document.getElementById('chartTo')?.value);
            currentRange = 'custom';
            setActiveRangeButton('custom');
            refresh();
        });
    }

    setInterval(refresh, Math.max(15000, (cfg.refreshSeconds || 60) * 1000));

    document.getElementById('themeToggle')?.addEventListener('click', function () {
        setTimeout(function () {
            render(lastPayload);
        }, 50);
    });
})();
