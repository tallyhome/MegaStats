<?php declare(strict_types=1);

$ip_matrix = $ip_matrix ?? null;
$scriptname = $scriptname ?? '';
$mail_url = $mail_url ?? ms_url($scriptname, ['page' => 'mail']);
$csrf_field = $csrf_field ?? '';

if (empty($ip_matrix['rows'])) {
    return;
}

$cell = static function (?bool $ok): string {
    if ($ok === true) {
        return '<span class="text-success" title="OK">✅</span>';
    }
    if ($ok === false) {
        return '<span class="text-danger" title="KO">❌</span>';
    }
    return '<span class="text-warning" title="Partiel">⚠️</span>';
};

$msCell = static function (array $row): string {
    $level = $row['level'] ?? 'unknown';
    return ms_mail_level_dot($level) . ' <span class="small">' . ms_e($row['label'] ?? '') . '</span>';
};

$gradeBadge = static function (array $grade): string {
    $g = $grade['grade'] ?? '?';
    $level = $grade['level'] ?? 'warn';
    $cls = match ($level) {
        'good' => 'success',
        'warn' => 'warning',
        default => 'danger',
    };
    return '<span class="badge text-bg-' . $cls . '">' . ms_e($g) . '</span>';
};
?>

<div class="card ms-card mb-3">
    <div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-table me-1"></i>Mail Configuration — par IP</span>
        <div class="d-flex flex-wrap gap-1">
            <form method="post" action="<?= ms_e($mail_url) ?>" class="d-inline">
                <?= $csrf_field ?>
                <input type="hidden" name="mail_action" value="scan_all">
                <button type="submit" class="btn btn-sm btn-primary">Analyser toutes les IP</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Compte</th>
                        <th>Mail IP</th>
                        <th>PTR</th>
                        <th>A</th>
                        <th>SPF</th>
                        <th>DKIM</th>
                        <th>DMARC</th>
                        <th>FCrDNS</th>
                        <th>HELO</th>
                        <th>RBL</th>
                        <th>Microsoft</th>
                        <th>Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ip_matrix['rows'] as $row): ?>
                    <tr>
                        <td class="font-monospace small">
                            <a href="<?= ms_e(ms_url($scriptname, ['page' => 'mail', 'ip' => $row['ip']])) ?>"><?= ms_e($row['ip']) ?></a>
                        </td>
                        <td class="small"><?= ms_e($row['account'] ?? '—') ?></td>
                        <td><?= $cell($row['mail_ip']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['ptr']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['a']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['spf']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['dkim']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['dmarc']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['fcrdns']['ok'] ?? null) ?></td>
                        <td><?= $cell($row['helo']['ok'] ?? null) ?></td>
                        <td>
                            <?php $listed = (int) ($row['rbl_listed'] ?? 0); ?>
                            <?php if ($listed > 0): ?>
                                <span class="badge text-bg-danger"><?= $listed ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-success">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= $msCell($row['microsoft'] ?? []) ?></td>
                        <td>
                            <?= $gradeBadge($row['grade'] ?? []) ?>
                            <span class="small text-secondary ms-1"><?= (int) ($row['score'] ?? 0) ?></span>
                        </td>
                        <td>
                            <form method="post" action="<?= ms_e($mail_url) ?>" class="d-inline">
                                <?= $csrf_field ?>
                                <input type="hidden" name="mail_action" value="scan_ip">
                                <input type="hidden" name="scan_ip" value="<?= ms_e($row['ip']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0" title="Analyser cette IP">↻</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
