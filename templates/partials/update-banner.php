<?php
$updateApi = $update_api_url ?? ms_url($scriptname ?? '', ['api' => 'update', 'action' => 'check']);
$updateWebCheck = $update_web_check_url ?? ms_url($scriptname ?? '', ['update_action' => 'check']);
$updateAlertClass = !empty($update_available) ? 'alert-info' : 'alert-secondary';
$useWhmForms = !empty($update_can_run) && !empty($whm_embedded);
?>
<?php if (!empty($update_flash)): ?>
<div class="card ms-card ms-alert-card border-info py-2 px-3 mb-3" role="status"><?= ms_e($update_flash) ?></div>
<?php endif; ?>
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
            <?php if ($useWhmForms): ?>
                <a href="<?= ms_e($updateWebCheck) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-repeat me-1"></i>Vérifier MAJ
                </a>
                <?php if (!empty($update_available)): ?>
                <form method="post" action="<?= ms_e($scriptname ?? '') ?>" class="d-inline"
                      onsubmit="return confirm('Installer MegaStats v<?= ms_e($update_latest ?? '') ?> ? (1 à 2 minutes)');">
                    <input type="hidden" name="update_action" value="run">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cloud-download me-1"></i>Mettre à jour
                    </button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-update-check" data-api-url="<?= ms_e($updateApi) ?>">Vérifier MAJ</button>
                <?php if (!empty($update_can_run)): ?>
                <button type="button" class="btn btn-sm btn-primary ms-update-run<?= empty($update_available) ? ' d-none' : '' ?>" data-api-url="<?= ms_e($updateApi) ?>">Mettre à jour</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
