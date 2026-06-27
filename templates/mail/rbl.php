<?php declare(strict_types=1);

$page_title = $page_title ?? 'Blacklist · MegaStats';
$selected_ip = $selected_ip ?? '';
$rbl = $rbl ?? ['all' => [], 'listed_count' => 0, 'total_zones' => 0];
$scan = $scan ?? null;

if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}

$listedCount = (int) ($rbl['listed_count'] ?? 0);
$totalZones = (int) ($rbl['total_zones'] ?? count($rbl['all'] ?? []));
$refreshUrl = ms_url($scriptname, ['page' => 'mail', 'ip' => $selected_ip, 'refresh' => '1']);
$primary_ip = $scan['ip'] ?? null;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-shield-exclamation me-2"></i>Blacklist : <?= ms_e($selected_ip) ?></h1>
        <div class="text-secondary small">Vérification DNSBL (type MXToolbox)</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= ms_e($mail_url ?? ms_url($scriptname, ['page' => 'mail'])) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Email &amp; IP
        </a>
        <a href="<?= ms_e($dashboard_url ?? $scriptname) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
        <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème"><i class="bi bi-moon-stars"></i></button>
        <a href="<?= ms_e($refreshUrl) ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Revérifier maintenant</a>
    </div>
</div>

<?php if (!empty($all_ips)): ?>
<?php require MEGASTATS_ROOT . '/templates/mail/partials/ip-list.php'; ?>
<?php endif; ?>

<?php if ($listedCount > 0): ?>
<div class="alert alert-danger py-2 mb-3" role="alert">
    <strong>LISTÉE :</strong> cette IP apparaît sur <?= $listedCount ?> liste(s) noire(s) sur <?= $totalZones ?> vérifiées.
</div>
<?php else: ?>
<div class="alert alert-success py-2 mb-3" role="alert">
    <strong>OK :</strong> aucune liste noire détectée sur <?= $totalZones ?> zones vérifiées.
</div>
<?php endif; ?>

<p class="text-secondary small mb-3">
    <?php if (!empty($rbl['from_cache']) && !empty($rbl['scan_ts'])): ?>
        Données du scan du <?= ms_e(date('d/m/Y H:i', (int) $rbl['scan_ts'])) ?> —
    <?php else: ?>
        Vérification en direct —
    <?php endif; ?>
    durée <?= (int) ($rbl['duration_ms'] ?? 0) ?> ms
</p>

<div class="card ms-card mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:120px">Statut</th>
                        <th>Blacklist</th>
                        <th>Raison</th>
                        <th class="text-end" style="width:100px">Temps</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = $rbl['all'] ?? [];
                    usort($rows, static function (array $a, array $b): int {
                        return ((int) ($b['listed'] ?? false)) <=> ((int) ($a['listed'] ?? false));
                    });
                    foreach ($rows as $item):
                        $isListed = !empty($item['listed']);
                    ?>
                    <tr class="<?= $isListed ? 'table-danger' : '' ?>">
                        <td>
                            <?php if ($isListed): ?>
                                <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>LISTED</span>
                            <?php else: ?>
                                <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>OK</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= ms_e($item['label'] ?? '') ?></td>
                        <td class="small text-secondary"><?= ms_e($item['reason'] ?? '') ?></td>
                        <td class="text-end small text-secondary"><?= (int) ($item['response_ms'] ?? 0) ?> ms</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/footer.php';
}
?>
