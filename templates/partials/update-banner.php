<?php if (!empty($update_available) && !empty($whm_embedded)): ?>
<div id="msUpdateBanner" class="alert alert-info py-2 mb-3" role="status"
     data-api-url="<?= ms_e($update_api_url ?? ms_url($scriptname ?? '', ['api' => 'update', 'action' => 'check'])) ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="bi bi-cloud-download me-1"></i>
            <span id="msUpdateStatus">MegaStats <strong><?= ms_e($update_latest ?? '') ?></strong> disponible (v<?= ms_e($version ?? '') ?> installée).</span>
        </div>
        <?php if (!empty($update_can_run)): ?>
        <button type="button" class="btn btn-sm btn-primary" id="msUpdateRun">Mettre à jour</button>
        <?php endif; ?>
    </div>
</div>
<?php elseif (empty($whm_embedded)): ?>
<div id="msUpdateBanner" class="alert alert-info d-none mb-3 py-2" role="status"
     data-api-url="<?= ms_e($update_api_url ?? ms_url($scriptname ?? '', ['api' => 'update', 'action' => 'check'])) ?>"
     data-csrf="<?= ms_e($_SESSION['ms_csrf_token'] ?? '') ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="bi bi-cloud-download me-1"></i>
            <span id="msUpdateStatus">Mise à jour MegaStats disponible.</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="msUpdateCheck">Revérifier</button>
            <?php if (!empty($update_can_run)): ?>
            <button type="button" class="btn btn-sm btn-primary" id="msUpdateRun">Mettre à jour</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
