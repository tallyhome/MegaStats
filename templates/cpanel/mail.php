<?php declare(strict_types=1);

$user = $user ?? '';
$ip = $ip ?? null;
$error = $error ?? null;
$checks = $checks ?? [];
$grouped = $grouped ?? ['families' => []];
$families = $grouped['families'] ?? [];
$listed_count = $listed_count ?? 0;
$delist_guide = $delist_guide ?? null;
$delist_zone = $delist_zone ?? null;

$icon = static function (?bool $ok): string {
    return match ($ok) {
        true => '<span class="text-success">✅</span>',
        false => '<span class="text-danger">❌</span>',
        default => '<span class="text-warning">⚠️</span>',
    };
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-envelope-check me-2"></i>Réputation mail</h1>
        <div class="text-secondary small">Compte <?= ms_e($user) ?> — votre IP uniquement</div>
    </div>
    <?php if ($ip !== null): ?>
    <a href="<?= ms_e($refresh_url ?? '#') ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Revérifier</a>
    <?php endif; ?>
</div>

<?php if ($error !== null): ?>
<div class="alert alert-warning"><?= ms_e($error) ?></div>
<?php else: ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-secondary small">Votre IP</div>
                <div class="font-monospace fw-semibold"><?= ms_e($ip) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-secondary small">Domaine</div>
                <div><?= ms_e($domain ?? '') ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-secondary small">Listes noires</div>
                <div class="fw-semibold <?= $listed_count > 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $listed_count ?> listée(s)</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Authentification mail</div>
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><?= $icon($checks['ptr']['ok'] ?? null) ?> PTR</li>
        <li class="list-group-item d-flex justify-content-between"><?= $icon($checks['fcrdns']['ok'] ?? null) ?> FCrDNS</li>
        <li class="list-group-item d-flex justify-content-between"><?= $icon($checks['spf']['ok'] ?? null) ?> SPF</li>
        <li class="list-group-item d-flex justify-content-between"><?= $icon($checks['dkim']['ok'] ?? null) ?> DKIM</li>
        <li class="list-group-item d-flex justify-content-between"><?= $icon($checks['dmarc']['ok'] ?? null) ?> DMARC</li>
    </ul>
</div>

<?php if ($delist_guide !== null): ?>
<div class="card mb-3 border-warning">
    <div class="card-header">Procédure de retrait — <?= ms_e($delist_zone ?? '') ?></div>
    <div class="card-body small">
        <a href="<?= ms_e($delist_guide['portal']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary mb-2">Portail officiel</a>
        <ol><?php foreach ($delist_guide['steps'] ?? [] as $s): ?><li><?= ms_e($s) ?></li><?php endforeach; ?></ol>
        <textarea class="form-control form-control-sm" rows="3" readonly onclick="this.select()"><?= ms_e(str_replace('{ip}', (string) $ip, (string) ($delist_guide['ticket'] ?? ''))) ?></textarea>
    </div>
</div>
<?php endif; ?>

<div class="accordion" id="cpanelRbl">
    <?php foreach ($families as $family):
        if (!($family['any_listed'] ?? false) && ($family['impact'] ?? '') !== 'critical') {
            continue;
        }
        $fid = 'cp-' . preg_replace('/[^a-z0-9]/', '', (string) $family['id']);
    ?>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button <?= ($family['any_listed'] ?? false) ? '' : 'collapsed' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#<?= ms_e($fid) ?>">
                <?= ms_e($family['label']) ?>
                <?= ($family['any_listed'] ?? false) ? '<span class="badge text-bg-danger ms-2">LISTED</span>' : '<span class="badge text-bg-success ms-2">OK</span>' ?>
            </button>
        </h2>
        <div id="<?= ms_e($fid) ?>" class="accordion-collapse collapse <?= ($family['any_listed'] ?? false) ? 'show' : '' ?>">
            <ul class="list-group list-group-flush small">
                <?php foreach ($family['items'] as $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= ms_e($item['label'] ?? '') ?> — <?= !empty($item['listed']) ? 'LISTED' : 'OK' ?></span>
                    <?php if (!empty($item['listed'])): ?>
                    <a href="<?= ms_e(ms_url($scriptname, ['delist' => $item['zone'] ?? ''])) ?>" class="btn btn-sm btn-outline-warning py-0">Procédure</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<p class="text-secondary small mt-3 mb-0">Pour modifier DNS ou PTR, contactez votre hébergeur. MegaStats v<?= ms_e($version ?? '') ?></p>
<?php endif; ?>
