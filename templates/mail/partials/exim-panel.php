<?php declare(strict_types=1);

$exim = $exim ?? null;
if ($exim === null) {
    return;
}

$icon = static function (?bool $ok): string {
    if ($ok === true) {
        return '<span class="text-success fw-bold">✔</span>';
    }
    if ($ok === false) {
        return '<span class="text-danger fw-bold">✖</span>';
    }
    return '<span class="text-secondary">—</span>';
};
?>

<div class="card ms-card mb-3">
    <div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-envelope-at me-1"></i>Exim — configuration sortante</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-secondary">Version</div>
                <div class="fw-semibold"><?= ms_e($exim['version'] !== '' ? $exim['version'] : '—') ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">Outgoing IP</div>
                <div><?= $icon($exim['outgoing_ip']['ok'] ?? null) ?> <?= ms_e($exim['outgoing_ip']['label'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">mailips</div>
                <div><?= $icon($exim['mailips']['ok'] ?? null) ?> <?= ms_e($exim['mailips']['label'] ?? '') ?>
                    <?php if (!empty($exim['mailips']['count'])): ?>
                        <span class="text-secondary small">(<?= (int) $exim['mailips']['count'] ?> entrée(s))</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">mailhelo</div>
                <div><?= $icon($exim['mailhelo']['ok'] ?? null) ?> <?= ms_e($exim['mailhelo']['label'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">Send mail from account IP</div>
                <div><?= $icon($exim['send_from_account_ip']['ok'] ?? null) ?> <?= ms_e($exim['send_from_account_ip']['label'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">Résultat</div>
                <?php if (!empty($exim['consistent'])): ?>
                    <div class="text-success fw-semibold">✔ Configuration cohérente</div>
                <?php else: ?>
                    <div class="text-warning fw-semibold">⚠ <?= ms_e($exim['result'] ?? 'Incohérence détectée') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($exim['issues'])): ?>
        <ul class="small text-secondary mb-0 mt-3">
            <?php foreach ($exim['issues'] as $issue): ?>
            <li><?= ms_e($issue) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
