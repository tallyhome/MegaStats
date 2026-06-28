<?php declare(strict_types=1);

$page_title = $page_title ?? 'Blacklist · MegaStats';
$selected_ip = $selected_ip ?? '';
$rbl = $rbl ?? ['all' => [], 'listed_count' => 0, 'total_zones' => 0];
$grouped = $rbl['grouped'] ?? ms_mail_group_rbl_by_family($rbl);
$families = $grouped['families'] ?? [];
$scan = $scan ?? null;
$delist_guide = $delist_guide ?? null;
$delist_zone = $delist_zone ?? null;

if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}

$listedCount = (int) ($grouped['listed_count'] ?? $rbl['listed_count'] ?? 0);
$totalZones = (int) ($grouped['total_zones'] ?? $rbl['total_zones'] ?? 0);
$criticalFamilies = (int) ($grouped['critical_families'] ?? 0);
$refreshUrl = ms_url($scriptname, ['page' => 'mail', 'ip' => $selected_ip, 'refresh' => '1']);
$primary_ip = $scan['ip'] ?? null;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-shield-exclamation me-2"></i>Blacklist : <?= ms_e($selected_ip) ?></h1>
        <div class="text-secondary small">RBL par famille — sous-listes détaillées</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= ms_e($mail_url ?? ms_url($scriptname, ['page' => 'mail'])) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Email &amp; IP
        </a>
        <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème"><i class="bi bi-moon-stars"></i></button>
        <form method="post" action="<?= ms_e($mail_url ?? ms_url($scriptname, ['page' => 'mail'])) ?>" class="d-inline">
            <?= $csrf_field ?? '' ?>
            <input type="hidden" name="mail_action" value="scan_ip">
            <input type="hidden" name="scan_ip" value="<?= ms_e($selected_ip) ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary">Analyser cette IP</button>
        </form>
        <a href="<?= ms_e($refreshUrl) ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Revérifier maintenant</a>
    </div>
</div>

<?php if (!empty($all_ips)): ?>
<?php require MEGASTATS_ROOT . '/templates/mail/partials/ip-list.php'; ?>
<?php endif; ?>

<?php if ($listedCount > 0): ?>
<div class="alert alert-danger py-2 mb-3" role="alert">
    <strong>LISTÉE :</strong> <?= $listedCount ?> sous-liste(s) sur <?= $totalZones ?> vérifiées
    <?php if ($criticalFamilies > 0): ?> · <strong><?= $criticalFamilies ?></strong> famille(s) critique(s)<?php endif; ?>.
</div>
<?php else: ?>
<div class="alert alert-success py-2 mb-3" role="alert">
    <strong>OK :</strong> aucune liste noire sur <?= $totalZones ?> zones.
</div>
<?php endif; ?>

<p class="text-secondary small mb-2">
    <?php if (!empty($rbl['from_cache']) && !empty($rbl['scan_ts'])): ?>
        Données du scan du <?= ms_e(date('d/m/Y H:i', (int) $rbl['scan_ts'])) ?> —
    <?php else: ?>
        Vérification en direct —
    <?php endif; ?>
    durée <?= (int) ($rbl['duration_ms'] ?? 0) ?> ms
</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="rblOpenAll"><i class="bi bi-arrows-expand me-1"></i>Tout ouvrir</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="rblCloseAll"><i class="bi bi-arrows-collapse me-1"></i>Tout fermer</button>
</div>

<?php if ($delist_guide !== null && $delist_zone !== null): ?>
<div class="card ms-card mb-3 border-warning">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-life-preserver me-1"></i>Procédure de retrait — <?= ms_e($delist_zone) ?></span>
        <a href="<?= ms_e(ms_url($scriptname, ['page' => 'mail', 'ip' => $selected_ip])) ?>" class="btn btn-sm btn-outline-secondary">Fermer</a>
    </div>
    <div class="card-body">
        <p class="mb-2"><a href="<?= ms_e($delist_guide['portal']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i><?= ms_e($delist_guide['portal_label'] ?? 'Portail officiel') ?>
        </a></p>
        <ol class="small mb-3">
            <?php foreach ($delist_guide['steps'] ?? [] as $step): ?>
            <li><?= ms_e($step) ?></li>
            <?php endforeach; ?>
        </ol>
        <div class="small text-secondary mb-1">Modèle de ticket (copier) :</div>
        <textarea class="form-control form-control-sm font-monospace" rows="4" readonly onclick="this.select()"><?= ms_e(str_replace('{ip}', $selected_ip, (string) ($delist_guide['ticket'] ?? ''))) ?></textarea>
        <div class="mt-2">
            <a href="<?= ms_e($refreshUrl) ?>" class="btn btn-sm btn-success">Revérifier après 24–48 h</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="accordion mb-3 ms-rbl-accordion" id="rblFamilies">
    <?php foreach ($families as $i => $family):
        $fid = 'fam-' . preg_replace('/[^a-z0-9_-]/', '', (string) $family['id']);
        $impactClass = match ($family['impact'] ?? 'info') {
            'critical' => 'danger',
            'important' => 'warning',
            default => 'secondary',
        };
    ?>
    <div class="accordion-item ms-card border mb-2">
        <h2 class="accordion-header">
            <button class="accordion-button <?= ($family['any_listed'] ?? false) ? '' : 'collapsed' ?> py-2"
                    type="button" data-bs-toggle="collapse" data-bs-target="#<?= ms_e($fid) ?>"
                    aria-expanded="<?= ($family['any_listed'] ?? false) ? 'true' : 'false' ?>"
                    aria-controls="<?= ms_e($fid) ?>">
                <span class="fw-semibold me-2"><?= ms_e($family['label']) ?></span>
                <span class="badge text-bg-<?= $impactClass ?> me-2"><?= ms_e($family['impact_label'] ?? '') ?></span>
                <?php if ($family['any_listed'] ?? false): ?>
                    <span class="badge text-bg-danger"><?= (int) $family['listed_count'] ?> LISTED</span>
                <?php else: ?>
                    <span class="badge text-bg-success">OK</span>
                <?php endif; ?>
                <span class="text-secondary small ms-2">(<?= (int) $family['total_count'] ?> sous-listes)</span>
            </button>
        </h2>
        <div id="<?= ms_e($fid) ?>" class="accordion-collapse collapse <?= ($family['any_listed'] ?? false) ? 'show' : '' ?>">
            <div class="accordion-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($family['items'] as $item):
                        $isListed = !empty($item['listed']);
                        $zone = (string) ($item['zone'] ?? '');
                    ?>
                        <tr class="<?= $isListed ? 'table-danger' : '' ?>">
                            <td style="width:100px">
                                <?= $isListed ? '<span class="badge text-bg-danger">LISTED</span>' : '<span class="badge text-bg-success">OK</span>' ?>
                            </td>
                            <td class="fw-semibold"><?= ms_e($item['label'] ?? '') ?></td>
                            <td class="small text-secondary"><?= ms_e($item['reason'] ?? '') ?></td>
                            <td class="text-end small"><?= (int) ($item['response_ms'] ?? 0) ?> ms</td>
                            <td class="text-end" style="width:160px">
                                <?php if ($isListed && $zone !== ''): ?>
                                <a href="<?= ms_e(ms_url($scriptname, ['page' => 'mail', 'ip' => $selected_ip, 'delist' => $zone])) ?>"
                                   class="btn btn-sm btn-warning text-dark fw-semibold ms-btn-delist">
                                    <i class="bi bi-life-preserver me-1"></i>Procédure retrait
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>window.MegaStatsRblAccordion = true;</script>

<?php
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/footer.php';
}
?>
