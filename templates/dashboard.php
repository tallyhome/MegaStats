<?php declare(strict_types=1);

$page_title = ($hostname ?? 'Server') . ' · MegaStats';
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}
?>

<?php require MEGASTATS_ROOT . '/templates/partials/update-banner.php'; ?>

<?php if (!empty($cleartmp_flash)): ?>
<div class="card ms-card ms-alert-card border-info py-2 px-3 mb-3" role="status"><?= ms_e($cleartmp_flash) ?></div>
<?php endif; ?>

<?php if (!empty($active_alerts)): ?>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card ms-card ms-alert-card border-warning mb-0">
            <div class="card-body py-3">
                <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1 text-warning"></i> Active alerts</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($active_alerts as $alert): ?>
                        <span class="badge text-bg-<?= ms_e(ms_alert_bootstrap_class($alert['status'])) ?>">
                            <i class="bi <?= ms_e($alert['icon']) ?> me-1"></i><?= ms_e($alert['message']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card ms-card ms-host-card">
            <div class="card-header ms-host-card-header d-flex flex-wrap justify-content-between align-items-start align-items-sm-center gap-2 py-3">
                <div class="min-w-0">
                    <h1 class="h4 mb-1 text-truncate"><?= ms_e($hostname) ?></h1>
                    <div class="text-secondary small"><?= ms_e($localtime) ?></div>
                    <?php if (!empty($uptime)): ?>
                        <div class="text-secondary small mt-1"><i class="bi bi-clock-history me-1"></i><?= ms_e($uptime) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-host-toolbar">
                    <a href="<?= ms_e(ms_url($scriptname, ['page' => 'config'])) ?>" class="btn btn-sm btn-outline-secondary" title="Modifier la configuration MegaStats">
                        <i class="bi bi-sliders me-1"></i>Config
                    </a>
                    <?php if (!empty($mail_enabled)): ?>
                        <a href="<?= ms_e($mail_url ?? ms_url($scriptname, ['page' => 'mail'])) ?>" class="btn btn-sm btn-outline-primary" title="Vérification délivrabilité email et blacklist IP">
                            <i class="bi bi-shield-check me-1"></i>Délivrabilité Email &amp; IP<?php if ($mail_score !== null): ?> <span class="badge text-bg-secondary ms-1"><?= (int) $mail_score ?></span><?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <span class="badge text-bg-secondary">v<?= ms_e($version ?? '') ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-update-check"
                            data-api-url="<?= ms_e($update_api_url ?? ms_url($scriptname, ['api' => 'update', 'action' => 'check'])) ?>"
                            title="Vérifier les mises à jour MegaStats">
                        <i class="bi bi-cloud-arrow-down me-1"></i>MAJ
                    </button>
                    <?php if (!empty($update_can_run)): ?>
                        <button type="button" class="btn btn-sm btn-info ms-update-run<?= empty($update_available) ? ' d-none' : '' ?>"
                                data-api-url="<?= ms_e($update_api_url ?? '') ?>"
                                title="Installer la mise à jour">
                            <i class="bi bi-cloud-download me-1"></i>Installer<?php if (!empty($update_latest)): ?> v<?= ms_e($update_latest) ?><?php endif; ?>
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($donate_url)): ?>
                        <a href="<?= ms_e($donate_url) ?>" class="btn btn-sm btn-warning" target="_blank" rel="noopener" title="Faire un don — PayPal">
                            <i class="bi bi-heart-fill me-1"></i>Don
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème clair / sombre" aria-label="Thème clair / sombre">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                </div>
            </div>
            <div class="card-body pt-2">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($services as $service): ?>
                        <span class="badge rounded-pill <?= $service['up'] ? 'text-bg-success' : 'text-bg-danger' ?>" title="<?= ms_e($service['name']) ?>">
                            <?= ms_e($service['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($stats as $stat): ?>
        <?php
        $cardClass = '';
        if (!empty($stat['alert']) && $stat['alert'] !== 'ok') {
            $cardClass = 'border-' . ms_alert_bootstrap_class($stat['alert']);
        }
        ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card ms-stat h-100 <?= ms_e($cardClass) ?>">
                <div class="card-body py-3">
                    <div class="text-secondary small text-uppercase"><?= ms_e($stat['label']) ?></div>
                    <div class="fs-4 fw-semibold">
                        <?php if (!empty($stat['link'])): ?>
                            <a href="<?= ms_e($stat['link']) ?>" class="text-decoration-none" <?= !empty($stat['popup']) ? 'onclick="' . ms_popup_js($stat['link'], 'users', 'width=625,height=300,scrollbars') . '"' : '' ?>>
                                <?= ms_e($stat['value']) ?><?php if ($stat['unit'] !== ''): ?><span class="fs-6 text-secondary"><?= ms_e($stat['unit']) ?></span><?php endif; ?>
                            </a>
                        <?php else: ?>
                            <?= ms_e($stat['value']) ?><?php if ($stat['unit'] !== ''): ?><span class="fs-6 text-secondary"><?= ms_e($stat['unit']) ?></span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Load average</div>
            <div class="card-body">
                <?php foreach ($loads as $load): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= ms_e($load['label']) ?></span>
                            <span><?= ms_e($load['value']) ?></span>
                        </div>
                        <div class="progress" role="progressbar" style="height:8px;">
                            <div class="progress-bar bg-<?= ms_e($load['level']) ?>" style="width: <?= (int) $load['percent'] ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Disk usage</div>
            <div class="card-body">
                <?php if ($disks === []): ?>
                    <p class="text-secondary mb-0">No disk data available.</p>
                <?php else: ?>
                    <?php foreach ($disks as $disk): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= ms_e($disk['mount']) ?> <span class="text-secondary">(<?= ms_e($disk['used']) ?> / <?= ms_e($disk['size']) ?>)</span></span>
                                <span><?= (int) $disk['percent'] ?>%</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-<?= ms_e($disk['level']) ?>" style="width: <?= (int) $disk['percent'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($vnstat_summary) && array_filter($vnstat_summary, static fn ($v) => $v !== null)): ?>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card ms-card">
            <div class="card-header fw-semibold"><i class="bi bi-graph-up me-1"></i> Network traffic (vnStat)</div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Today</div>
                        <div class="fw-semibold"><?= ms_e(ms_format_traffic_mb(isset($vnstat_summary['today_mb']) ? (float) $vnstat_summary['today_mb'] : null)) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Yesterday</div>
                        <div class="fw-semibold"><?= ms_e(ms_format_traffic_mb(isset($vnstat_summary['yesterday_mb']) ? (float) $vnstat_summary['yesterday_mb'] : null)) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Current month</div>
                        <div class="fw-semibold"><?= ms_e(ms_format_traffic_mb(isset($vnstat_summary['month_mb']) ? (float) $vnstat_summary['month_mb'] : null)) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Total</div>
                        <div class="fw-semibold"><?= ms_e(ms_format_traffic_mb(isset($vnstat_summary['total_mb']) ? (float) $vnstat_summary['total_mb'] : null)) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card ms-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold"><i class="bi bi-bar-chart-line me-1"></i> Metrics history</span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Période historique" id="chartRangeGroup">
                        <button type="button" class="btn btn-outline-secondary" data-range="1d">1 jour</button>
                        <button type="button" class="btn btn-outline-secondary" data-range="1w">1 semaine</button>
                        <button type="button" class="btn btn-outline-secondary" data-range="1m">1 mois</button>
                        <button type="button" class="btn btn-outline-secondary" data-range="custom" title="Période personnalisée">Perso</button>
                    </div>
                    <span class="badge text-bg-secondary" id="chartsUpdated">Live</span>
                </div>
            </div>
            <div class="card-body">
                <div id="chartCustomRange" class="row g-2 align-items-end mb-3 d-none">
                    <div class="col-md-4">
                        <label class="form-label small mb-0" for="chartFrom">Du</label>
                        <input type="datetime-local" class="form-control form-control-sm" id="chartFrom">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0" for="chartTo">Au</label>
                        <input type="datetime-local" class="form-control form-control-sm" id="chartTo">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-sm btn-primary" id="chartCustomApply">Appliquer</button>
                    </div>
                </div>
                <?php if (empty($history_writable)): ?>
                    <?php $writeReport = $history_write_report ?? []; ?>
                    <div class="alert alert-danger py-2">
                        Le dossier métriques n'est pas accessible en écriture. Les graphiques ne peuvent pas enregistrer l'historique.
                        <?php if (!empty($writeReport['reason'])): ?>
                            <br><small><?= ms_e($writeReport['reason']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($writeReport['path'])): ?>
                            <br><small>Chemin : <code><?= ms_e($writeReport['path']) ?></code> — PHP : <code><?= ms_e($writeReport['php_user'] ?? '?') ?></code></small>
                        <?php endif; ?>
                    </div>
                <?php elseif (($history_points ?? 0) < 2): ?>
                    <div class="alert alert-info py-2">
                        Historique insuffisant (<?= (int) ($history_points ?? 0) ?> point<?= ($history_points ?? 0) > 1 ? 's' : '' ?>).
                        Le cron <code>cron.php</code> doit tourner chaque minute pour remplir l'historique (1 jour à 1 mois selon rétention).
                    </div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartCpu"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartMemory"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartLoad"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartDisk"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartNetwork"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartUsers"></canvas></div></div>
                    <div class="col-md-6 col-xl-4"><div class="ms-chart-wrap"><canvas id="chartDiskMounts"></canvas></div></div>
                </div>
                <p class="text-secondary small mb-0 mt-3">
                    Rétention jusqu'à <?= number_format((int) ($history_max_points ?? 43200)) ?> points · affichage max <?= (int) ($history_chart_max_points ?? 300) ?> points · collecte cron chaque minute
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($panels as $panel): ?>
        <div class="col-12 col-xl-6">
            <div class="card ms-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold"><?= ms_e($panel['title']) ?></span>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($panel['clear_tmp'])): ?>
                            <form method="post" action="<?= ms_e($scriptname) ?>" class="d-inline" onsubmit="return confirm('Supprimer les fichiers de /tmp (hors sess_*, systemd, sockets) de plus d\'1 h ?');">
                                <?= $csrf_field ?>
                                <input type="hidden" name="cleartmp" value="1">
                                <input type="hidden" name="confirm" value="yes">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash3 me-1"></i>Vider /tmp
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (!empty($panel['actions'])): ?>
                            <div class="btn-group btn-group-sm">
                                <?php foreach ($panel['actions'] as $action): ?>
                                    <?php
                                    $onclick = !empty($action['popup'])
                                        ? ms_popup_js($action['url'], $action['window'] ?? 'popup', 'width=' . ($action['size'] ?? '600,480') . ',resizable,scrollbars')
                                        : '';
                                    ?>
                                    <a href="<?= ms_e($action['url']) ?>" class="btn btn-outline-secondary btn-sm" <?= $onclick ? 'onclick="' . $onclick . '"' : '' ?>>
                                        <?= ms_e($action['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($panel['note'])): ?>
                        <div class="small text-secondary px-3 pt-2"><?= ms_e($panel['note']) ?></div>
                    <?php endif; ?>
                    <pre class="ms-pre <?= !empty($panel['scroll']) ? 'ms-pre-scroll' : '' ?>"><?= $panel['content'] ?></pre>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="col-12 col-xl-6">
        <div class="card ms-card">
            <div class="card-header fw-semibold">Whois lookup</div>
            <div class="card-body">
                <form method="post" action="<?= ms_e($scriptname) ?>" class="row g-2 align-items-end" onsubmit="return this.whois.value.trim() !== '';">
                    <?= $csrf_field ?>
                    <div class="col-md-8">
                        <label class="form-label" for="whois">IP or domain</label>
                        <input type="text" class="form-control" id="whois" name="whois" placeholder="example.com">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lookup</button>
                        <button type="reset" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.MegaStatsCharts = {
    initial: <?= $charts_json ?? '{}' ?>,
    apiUrl: <?= json_encode($metrics_api_url ?? '', JSON_THROW_ON_ERROR) ?>,
    refreshSeconds: <?= (int) ($chart_refresh_seconds ?? 60) ?>,
    range: <?= json_encode($chart_range ?? '1d', JSON_THROW_ON_ERROR) ?>,
    fromTs: <?= json_encode($chart_from, JSON_THROW_ON_ERROR) ?>,
    toTs: <?= json_encode($chart_to, JSON_THROW_ON_ERROR) ?>
};
</script>

<?php
$include_charts = true;
require MEGASTATS_ROOT . '/templates/partials/footer.php';
?>
