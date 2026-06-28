<?php declare(strict_types=1);

$page_title = $page_title ?? 'Server Toolkit · MegaStats';

if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= ms_e($dashboard_url ?? $scriptname) ?>">MegaStats</a></li>
                <li class="breadcrumb-item active" aria-current="page">Server Toolkit</li>
            </ol>
        </nav>
        <h1 class="h4 mb-1"><i class="bi bi-tools me-2"></i>OBI2 Server Toolkit v1.0</h1>
        <div class="text-secondary small">Gestion serveur cPanel/WHM — comptes, IP, SSL, rapports</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= ms_e($dashboard_url ?? $scriptname) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard MegaStats
        </a>
        <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème"><i class="bi bi-moon-stars"></i></button>
    </div>
</div>

<div class="card ms-card mb-3">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <div class="fw-semibold mb-1"><i class="bi bi-terminal me-1"></i>Menu interactif SSH (root)</div>
                <code class="small user-select-all"><?= ms_e($cli_path ?? '/opt/megastats/toolkit/server-toolkit.sh') ?></code>
                <div class="text-secondary small mt-2">Comptes, IP, Laravel, WordPress, permissions — actions interactives complètes.</div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge text-bg-info">WHM root</span>
                <span class="badge text-bg-secondary">v1.0</span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($action_result)): ?>
<div class="card ms-card mb-3 <?= !empty($action_result['ok']) ? 'border-success' : 'border-warning' ?>">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>Résultat : <?= ms_e($action_result['label'] ?? $action_id ?? 'Action') ?></span>
        <a href="<?= ms_e($toolkit_url ?? ms_url($scriptname, ['page' => 'toolkit'])) ?>" class="btn btn-sm btn-outline-secondary">Fermer</a>
    </div>
    <div class="card-body p-0">
        <pre class="small mb-0 p-3 ms-toolkit-output" style="max-height:420px;overflow:auto"><?= ms_e($action_result['output'] ?? '') ?></pre>
    </div>
</div>
<?php endif; ?>

<?php foreach ($categories ?? [] as $catKey => $group): ?>
<?php if (empty($group['items'])) { continue; } ?>
<div class="mb-4">
    <h2 class="h6 text-uppercase text-secondary mb-2">
        <i class="bi <?= ms_e($group['meta']['icon'] ?? 'bi-grid') ?> me-1"></i><?= ms_e($group['meta']['label'] ?? $catKey) ?>
    </h2>
    <div class="row g-3">
        <?php foreach ($group['items'] as $item): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card ms-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi <?= ms_e($item['icon'] ?? 'bi-circle') ?> fs-5 text-primary"></i>
                        <div>
                            <div class="fw-semibold"><?= ms_e($item['label']) ?></div>
                            <?php if (!empty($item['num'])): ?>
                            <span class="badge text-bg-light text-dark border">#<?= (int) $item['num'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['soon'])): ?>
                            <span class="badge text-bg-warning text-dark">Bientôt</span>
                            <?php elseif (!empty($item['web'])): ?>
                            <span class="badge text-bg-success">Web</span>
                            <?php else: ?>
                            <span class="badge text-bg-secondary">SSH</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-auto d-flex flex-wrap gap-1">
                        <?php if (!empty($item['web']) && empty($item['soon'])): ?>
                        <a href="<?= ms_e($ms_link(['action' => $item['id']])) ?>"
                           class="btn btn-sm btn-primary">Exécuter</a>
                        <?php endif; ?>
                        <?php if (!empty($item['cli'])): ?>
                        <span class="btn btn-sm btn-outline-secondary disabled" title="Menu SSH option <?= (int) ($item['num'] ?? 0) ?>">CLI #<?= (int) ($item['num'] ?? 0) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/footer.php';
}
?>
