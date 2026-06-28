<?php

declare(strict_types=1);

function ms_mail_export_html(array $config, ?array $scan = null): string
{
    $scan ??= ms_mail_load_latest($config);
    if ($scan === null) {
        $scan = ms_mail_run_scan($config);
    }

    $score = (int) ($scan['score'] ?? 0);
    $grade = ms_mail_grade_from_score($score);
    $breakdown = ms_mail_score_breakdown($scan);
    $matrix = $scan['ip_matrix']['rows'] ?? [];

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Rapport MegaStats Mail</title>
<style>body{font-family:sans-serif;margin:2rem;color:#111}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px;font-size:12px}h1{font-size:20px}.bad{color:#c00}.good{color:#080}</style>
</head><body>
<h1>Rapport délivrabilité mail — MegaStats</h1>
<p>Date : <?= htmlspecialchars(date('Y-m-d H:i', (int) ($scan['ts'] ?? time()))) ?></p>
<p>Domaine : <?= htmlspecialchars((string) ($scan['domain'] ?? '')) ?> · IP : <?= htmlspecialchars((string) ($scan['ip'] ?? '')) ?></p>
<p><strong>Score : <?= $score ?>/100 (<?= htmlspecialchars($grade['grade']) ?>)</strong></p>
<h2>Pénalités</h2>
<ul><?php foreach ($breakdown as $b): ?><li><?= htmlspecialchars($b['label']) ?> : <?= (int) $b['delta'] ?> — <?= htmlspecialchars((string) $b['reason']) ?></li><?php endforeach; ?></ul>
<?php if ($matrix !== []): ?>
<h2>Matrice IP</h2>
<table><tr><th>IP</th><th>Compte</th><th>Score</th><th>RBL</th><th>FCrDNS</th><th>Mail IP</th><th>Microsoft</th></tr>
<?php foreach ($matrix as $row): ?>
<tr>
<td><?= htmlspecialchars((string) $row['ip']) ?></td>
<td><?= htmlspecialchars((string) ($row['account'] ?? '—')) ?></td>
<td><?= (int) ($row['score'] ?? 0) ?> (<?= htmlspecialchars($row['grade']['grade'] ?? '') ?>)</td>
<td><?= (int) ($row['rbl_listed'] ?? 0) ?></td>
<td><?= !empty($row['fcrdns']['ok']) ? 'OK' : 'KO' ?></td>
<td><?= !empty($row['mail_ip']['ok']) ? 'OK' : 'KO' ?></td>
<td><?= htmlspecialchars((string) ($row['microsoft']['label'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p style="margin-top:2rem;font-size:11px;color:#666">MegaStats v<?= htmlspecialchars((string) ($config['version'] ?? '')) ?></p>
</body></html>
    <?php
    return (string) ob_get_clean();
}

function ms_mail_handle_export(array $config): bool
{
    if ((string) ms_get('page', '') !== 'mail' || (string) ms_get('export', '') !== '1') {
        return false;
    }

    $html = ms_mail_export_html($config);
    $filename = 'megastats-mail-' . date('Y-m-d') . '.html';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $html;

    return true;
}
