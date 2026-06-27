<?php
$updateApi = $update_api_url ?? ms_url($scriptname ?? '', ['api' => 'update', 'action' => 'check']);
$updateAlertClass = !empty($update_available) ? 'alert-info' : 'alert-secondary';
?>
<div id="msUpdateBanner" class="alert <?= $updateAlertClass ?> py-2 mb-3" role="status"
     data-api-url="<?= ms_e($updateApi) ?>"
     data-csrf="<?= ms_e($_SESSION['ms_csrf_token'] ?? '') ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="bi bi-cloud-arrow-down me-1"></i>
            <span id="msUpdateStatus">
                <?php if (!empty($update_available)): ?>
                    MegaStats <strong><?= ms_e($update_latest ?? '') ?></strong> disponible (v<?= ms_e($version ?? '') ?> installée).
                <?php else: ?>
                    MegaStats v<?= ms_e($version ?? '') ?> — à jour.
                <?php endif; ?>
            </span>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <button type="button" class="btn btn-sm btn-outline-secondary ms-update-check" data-api-url="<?= ms_e($updateApi) ?>">Vérifier MAJ</button>
            <?php if (!empty($update_can_run)): ?>
            <button type="button" class="btn btn-sm btn-primary ms-update-run<?= empty($update_available) ? ' d-none' : '' ?>" data-api-url="<?= ms_e($updateApi) ?>">Mettre à jour</button>
            <?php endif; ?>
        </div>
    </div>
</div>
