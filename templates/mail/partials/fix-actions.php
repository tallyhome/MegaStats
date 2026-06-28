<?php declare(strict_types=1);

$auto_fix_plan = $auto_fix_plan ?? null;
$mailips_rebuild_needed = $mailips_rebuild_needed ?? false;
$mailips_preview = $mailips_preview ?? null;
$exim = $exim ?? null;
$scan = $scan ?? null;
$can_scan = $can_scan ?? false;
$csrf_field = $csrf_field ?? '';
$mail_url = $mail_url ?? ms_url($scriptname ?? '', ['page' => 'mail']);

$dnsFixAvailable = $auto_fix_plan !== null && ms_mail_auto_fix_has_actions($auto_fix_plan);
$show = $can_scan && ($dnsFixAvailable || $mailips_rebuild_needed);

if (!$show) {
    return;
}

$dnsLabels = $dnsFixAvailable ? ms_mail_auto_fix_action_labels($auto_fix_plan) : [];
$fixIp = (string) ($auto_fix_plan['ip'] ?? $scan['ip'] ?? '');
$ptrNote = (string) ($auto_fix_plan['ptr_note'] ?? '');
?>

<div class="card ms-card border-warning mb-3">
    <div class="card-header fw-semibold bg-warning-subtle">
        <i class="bi bi-tools me-1"></i>Problèmes détectés — actions correctives
    </div>
    <div class="card-body">
        <?php if ($dnsFixAvailable): ?>
        <div class="mb-4 pb-3 border-bottom">
            <h2 class="h6 fw-semibold mb-2">DNS · IP <?= ms_e($fixIp) ?></h2>
            <p class="small text-secondary mb-2">MegaStats peut corriger automatiquement (via cPanel uapi, root requis) :</p>
            <ul class="small mb-3">
                <?php foreach ($dnsLabels as $label): ?>
                <li><?= ms_e($label) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($ptrNote !== ''): ?>
            <p class="small text-secondary mb-2"><i class="bi bi-info-circle me-1"></i><?= ms_e($ptrNote) ?> — non automatisable.</p>
            <?php endif; ?>
            <form method="post" action="<?= ms_e($mail_url) ?>" class="d-inline"
                  onsubmit="return confirm('Appliquer ces corrections DNS pour <?= ms_e($fixIp) ?> ?\n\n<?= ms_e(implode("\n", $dnsLabels)) ?>');">
                <?= $csrf_field ?>
                <input type="hidden" name="mail_action" value="auto_fix_ip">
                <input type="hidden" name="fix_ip" value="<?= ms_e($fixIp) ?>">
                <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">
                    <i class="bi bi-magic me-1"></i>Corriger DNS automatiquement (<?= count($dnsLabels) ?> action<?= count($dnsLabels) > 1 ? 's' : '' ?>)
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($mailips_rebuild_needed): ?>
        <div>
            <h2 class="h6 fw-semibold mb-2">Exim · /etc/mailips</h2>
            <?php if (!empty($exim['issues'])): ?>
            <ul class="small text-secondary mb-2">
                <?php foreach ($exim['issues'] as $issue): ?>
                <li><?= ms_e($issue) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if ($mailips_preview !== null && ($mailips_preview['count'] ?? 0) > 0): ?>
            <p class="small mb-2">
                Reconstruction possible : <strong><?= (int) $mailips_preview['count'] ?></strong> entrée(s) domaine → IP → compte cPanel.
            </p>
            <ul class="small font-monospace text-secondary mb-3">
                <?php foreach ($mailips_preview['entries'] ?? [] as $entry): ?>
                <li><?= ms_e($entry['domain'] . ': ' . $entry['ip'] . ' : ' . $entry['user']) ?></li>
                <?php endforeach; ?>
                <?php if (($mailips_preview['count'] ?? 0) > 5): ?>
                <li>… et <?= (int) $mailips_preview['count'] - 5 ?> autre(s)</li>
                <?php endif; ?>
            </ul>
            <?php else: ?>
            <p class="small text-secondary mb-3">Aucune entrée détectée dans les comptes cPanel — vérifiez /etc/userdomains.</p>
            <?php endif; ?>
            <form method="post" action="<?= ms_e($mail_url) ?>" class="d-inline"
                  onsubmit="return confirm('Reconstruire /etc/mailips depuis tous les comptes cPanel et redémarrer Exim ?\n\nUn backup sera créé avant écriture.');">
                <?= $csrf_field ?>
                <input type="hidden" name="mail_action" value="rebuild_mailips">
                <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">
                    <i class="bi bi-arrow-repeat me-1"></i>Reconstruire /etc/mailips depuis cPanel
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
