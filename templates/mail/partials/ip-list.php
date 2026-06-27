<?php declare(strict_types=1);
/** @var list<string> $all_ips */
/** @var array<string, array{listed?: int|null, url?: string}> $ip_summaries */
/** @var string|null $selected_ip IP courante (surbrillance) */
/** @var string|null $primary_ip IP principale du scan (badge) */

$all_ips = $all_ips ?? [];
$ip_summaries = $ip_summaries ?? [];
$selected_ip = $selected_ip ?? null;
$primary_ip = $primary_ip ?? null;

if ($all_ips === []) {
    return;
}
?>
<div class="card ms-card mb-3">
    <div class="card-header fw-semibold"><i class="bi bi-hdd-network me-1"></i>Adresses IP du serveur</div>
    <div class="card-body">
        <p class="text-secondary small mb-3">Cliquez sur une IP pour voir le détail complet des listes noires (DNSBL).</p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($all_ips as $ipAddr):
                $summary = $ip_summaries[$ipAddr] ?? [];
                $listed = $summary['listed'] ?? null;
                $isSelected = $selected_ip !== null && $ipAddr === $selected_ip;
                $isPrimary = $primary_ip !== null && $ipAddr === $primary_ip;
                $btnClass = $isSelected ? 'btn-primary' : ($isPrimary ? 'btn-outline-primary' : 'btn-outline-secondary');
            ?>
            <a href="<?= ms_e($summary['url'] ?? ms_url($scriptname, ['page' => 'mail', 'ip' => $ipAddr])) ?>"
               class="btn btn-sm <?= $btnClass ?><?= $isSelected ? ' active' : '' ?>">
                <?= ms_e($ipAddr) ?>
                <?php if ($isSelected): ?><span class="badge text-bg-light text-dark ms-1">actuelle</span><?php endif; ?>
                <?php if (!$isSelected && $isPrimary): ?><span class="badge text-bg-primary ms-1">principale</span><?php endif; ?>
                <?php if ($listed !== null && $listed > 0): ?>
                    <span class="badge text-bg-danger ms-1"><?= (int) $listed ?> RBL</span>
                <?php elseif ($listed === 0): ?>
                    <span class="badge text-bg-success ms-1">OK</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
