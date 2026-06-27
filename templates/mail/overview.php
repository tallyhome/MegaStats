<?php declare(strict_types=1);

$page_title = $page_title ?? 'Mail & délivrabilité · MegaStats';
$scan = $scan ?? null;
$history = $history ?? [];
$history_json = $history_json ?? '[]';

if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}

$statusIcon = static function (?bool $ok): string {
    if ($ok === true) {
        return '<span class="text-success fw-bold">✔</span>';
    }
    if ($ok === false) {
        return '<span class="text-danger fw-bold">✖</span>';
    }
    return '<span class="text-secondary">—</span>';
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-envelope-check me-2"></i>Mail & délivrabilité</h1>
        <div class="text-secondary small">Surveillance DNS, RBL, SMTP et réputation</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= ms_e($dashboard_url ?? $scriptname) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Dashboard système
        </a>
        <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème"><i class="bi bi-moon-stars"></i></button>
        <?php if (!empty($can_scan)): ?>
        <form method="post" action="<?= ms_e($mail_url ?? ms_url($scriptname, ['page' => 'mail'])) ?>" class="d-inline">
            <?= $csrf_field ?? '' ?>
            <input type="hidden" name="mail_action" value="scan">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Relancer l'analyse</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($scan_flash)): ?>
<div class="card ms-card ms-alert-card border-info py-2 px-3 mb-3"><?= ms_e($scan_flash) ?></div>
<?php endif; ?>

<?php if (empty($storage_ok)): ?>
<div class="alert alert-danger">Dossier mail non accessible en écriture. Vérifiez les permissions de /var/cpanel/megastats/mail</div>
<?php endif; ?>

<?php if ($scan === null): ?>
<div class="card ms-card mb-3">
    <div class="card-body">
        <p class="mb-3">Aucune analyse enregistrée. Lancez un premier scan (cron quotidien ou bouton ci-dessus).</p>
    </div>
</div>
<?php else: ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card ms-card h-100 text-center">
            <div class="card-body">
                <div class="text-secondary small text-uppercase">Score global</div>
                <div class="display-4 fw-bold"><?= (int) ($scan['score'] ?? 0) ?></div>
                <div class="text-secondary">/ 100</div>
                <div class="small text-secondary mt-2">Dernière analyse : <?= ms_e(date('d/m/Y H:i', (int) ($scan['ts'] ?? time()))) ?></div>
                <div class="small">IP <?= ms_e($scan['ip'] ?? '?') ?> · <?= ms_e($scan['domain'] ?? '?') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Évolution réputation (90 j)</div>
            <div class="card-body">
                <div class="ms-chart-wrap" style="height:180px"><canvas id="mailChartScore"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">DNS & SMTP</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 ms-mail-checklist">
                    <li><?= $statusIcon($scan['dns']['spf']['ok'] ?? false) ?> SPF <span class="text-secondary small ms-2"><?= ms_e($scan['dns']['spf']['detail'] ?? '') ?></span></li>
                    <li><?= $statusIcon($scan['dns']['dkim']['ok'] ?? false) ?> DKIM</li>
                    <li><?= $statusIcon($scan['dns']['dmarc']['ok'] ?? false) ?> DMARC</li>
                    <li><?= $statusIcon($scan['dns']['ptr']['ok'] ?? false) ?> rDNS (PTR)</li>
                    <li class="border-top my-2"></li>
                    <li><?= $statusIcon($scan['smtp']['tls']['ok'] ?? false) ?> TLS</li>
                    <li><?= $statusIcon($scan['smtp']['banner']['ok'] ?? false) ?> SMTP Banner</li>
                    <li><?= $statusIcon($scan['smtp']['helo']['ok'] ?? false) ?> HELO/EHLO</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">RBL <span class="badge text-bg-secondary ms-1"><?= (int) ($scan['rbl_listed'] ?? 0) ?> listée(s)</span></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 ms-mail-checklist">
                    <?php foreach ($scan['rbl_featured'] ?? [] as $item): ?>
                        <li><?= ($item['listed'] ?? false) ? '<span class="text-danger fw-bold">✖</span>' : '<span class="text-success fw-bold">✔</span>' ?> <?= ms_e($item['label'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (($scan['rbl_listed'] ?? 0) === 0): ?>
                    <p class="text-secondary small mb-0 mt-2">Principales listes OK (<?= count(ms_mail_rbl_zones()) ?> vérifiées).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Microsoft</div>
            <div class="card-body">
                <p class="mb-1"><?= ms_mail_level_dot($scan['microsoft']['level'] ?? 'unknown') ?> <?= ms_e($scan['microsoft']['label'] ?? '') ?></p>
                <p class="text-secondary small mb-0">SNDS : <?= ms_e($scan['microsoft']['detail'] ?? '') ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Google</div>
            <div class="card-body">
                <p class="mb-1">Postmaster : <?= ms_mail_level_dot($scan['google']['level'] ?? 'unknown') ?> <?= ms_e($scan['google']['label'] ?? '') ?></p>
                <p class="text-secondary small mb-0"><?= ms_e($scan['google']['detail'] ?? '') ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Yahoo</div>
            <div class="card-body">
                <p class="mb-1"><?= $statusIcon($scan['yahoo']['ok'] ?? null) ?> <?= ms_e($scan['yahoo']['detail'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Tests SMTP (connectivité MX)</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 ms-mail-checklist">
                    <?php foreach ($scan['smtp_tests'] ?? [] as $name => $test): ?>
                        <li><?= $statusIcon($test['ok'] ?? false) ?> <?= ms_e(ucfirst((string) $name)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-secondary small mb-0 mt-2">Test de bannière MX (pas d'envoi réel). Configurez les boîtes test dans config/mail.php pour des tests inbox complets.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card ms-card h-100">
            <div class="card-header fw-semibold">Score SpamAssassin</div>
            <div class="card-body">
                <?php if (($scan['spamassassin']['score'] ?? null) !== null): ?>
                    <div class="fs-2 fw-semibold"><?= ms_e((string) $scan['spamassassin']['score']) ?> <span class="fs-6 text-secondary">/ <?= (int) ($scan['spamassassin']['max'] ?? 10) ?></span></div>
                <?php else: ?>
                    <p class="text-secondary mb-0"><?= ms_e($scan['spamassassin']['detail'] ?? 'N/A') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card ms-card">
            <div class="card-header fw-semibold">Expiration / statut</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($scan['expirations'] ?? [] as $item): ?>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-2 h-100 <?= !empty($item['ok']) ? '' : 'border-warning' ?>">
                                <div class="fw-semibold"><?= ms_e($item['label'] ?? '') ?></div>
                                <div class="small"><?= ms_e($item['expires'] ?? '—') ?></div>
                                <?php if (isset($item['days']) && $item['days'] !== null): ?>
                                    <div class="small text-secondary"><?= (int) $item['days'] ?> jours</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.MegaStatsMail = { history: <?= $history_json ?> };
</script>

<?php endif; ?>

<?php
$include_mail_js = true;
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/footer.php';
}
?>
