<?php declare(strict_types=1);

$page_title = $page_title ?? 'Configuration · MegaStats';
$definitions = $definitions ?? [];
$active_file = $active_file ?? 'app';
$fields = $fields ?? [];

if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/header.php';
}

$renderField = static function (array $field) use ($active_file): void {
    $key = $field['key'];
    $type = $field['type'];
    $label = $field['label'] ?? $key;
    $value = $field['value'] ?? '';
    $hint = $field['hint'] ?? '';
    $id = 'cfg_' . preg_replace('/[^a-z0-9_]/i', '_', $key);
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold" for="<?= ms_e($id) ?>"><?= ms_e($label) ?></label>
        <?php if ($hint !== ''): ?>
            <div class="form-text mb-1"><?= ms_e($hint) ?></div>
        <?php endif; ?>

        <?php if ($type === 'readonly'): ?>
            <input type="text" class="form-control form-control-sm" id="<?= ms_e($id) ?>" value="<?= ms_e((string) $value) ?>" readonly disabled>
        <?php elseif ($type === 'bool'): ?>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="<?= ms_e($id) ?>"
                       name="cfg[<?= ms_e($key) ?>]" value="1"<?= !empty($value) ? ' checked' : '' ?>>
                <label class="form-check-label text-secondary" for="<?= ms_e($id) ?>"><?= !empty($value) ? 'Activé' : 'Désactivé' ?></label>
            </div>
        <?php elseif ($type === 'password_hash'): ?>
            <input type="text" class="form-control form-control-sm font-monospace" id="<?= ms_e($id) ?>" value="•••••••• (hash bcrypt enregistré)" readonly disabled>
        <?php elseif ($type === 'new_password'): ?>
            <input type="password" class="form-control form-control-sm" id="<?= ms_e($id) ?>" name="cfg[new_password]" value="" autocomplete="new-password" placeholder="Laisser vide pour ne pas changer">
        <?php elseif ($type === 'lines'): ?>
            <textarea class="form-control form-control-sm font-monospace" id="<?= ms_e($id) ?>" name="cfg[<?= ms_e($key) ?>]" rows="4"><?= ms_e(ms_config_format_lines(is_array($value) ? $value : [])) ?></textarea>
        <?php elseif ($type === 'map'): ?>
            <?php if (is_array($value) && $value !== []): ?>
                <?php foreach ($value as $mapKey => $mapVal): ?>
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text"><?= ms_e((string) $mapKey) ?></span>
                        <input type="text" class="form-control" name="cfg[<?= ms_e($key) ?>][<?= ms_e((string) $mapKey) ?>]" value="<?= ms_e((string) $mapVal) ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <textarea class="form-control form-control-sm font-monospace" id="<?= ms_e($id) ?>" name="cfg[<?= ms_e($key) ?>]" rows="3" placeholder="cle=valeur"><?= ms_e(is_array($value) ? ms_config_format_map($value) : '') ?></textarea>
            <?php endif; ?>
        <?php elseif ($type === 'int' || $type === 'float'): ?>
            <input type="number" step="<?= $type === 'float' ? '0.1' : '1' ?>" class="form-control form-control-sm" id="<?= ms_e($id) ?>"
                   name="cfg[<?= ms_e($key) ?>]" value="<?= ms_e((string) $value) ?>">
        <?php else: ?>
            <input type="text" class="form-control form-control-sm" id="<?= ms_e($id) ?>"
                   name="cfg[<?= ms_e($key) ?>]" value="<?= ms_e((string) $value) ?>">
        <?php endif; ?>
    </div>
    <?php
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= ms_e($dashboard_url ?? $scriptname) ?>">MegaStats</a></li>
                <li class="breadcrumb-item active" aria-current="page">Configuration</li>
            </ol>
        </nav>
        <h1 class="h4 mb-1"><i class="bi bi-sliders me-2"></i>Configuration</h1>
        <div class="text-secondary small">Modifier les fichiers <code>config/*.php</code> depuis le dashboard</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= ms_e($dashboard_url ?? $scriptname) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard MegaStats
        </a>
        <?php if (!empty($mail_url)): ?>
        <a href="<?= ms_e($mail_url) ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-shield-check me-1"></i>Email &amp; IP
        </a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-secondary" id="themeToggle" title="Thème"><i class="bi bi-moon-stars"></i></button>
    </div>
</div>

<?php if (!empty($config_flash)): ?>
<div class="card ms-card ms-alert-card border-info py-2 px-3 mb-3"><?= ms_e($config_flash) ?></div>
<?php endif; ?>

<?php if (empty($config_writable)): ?>
<div class="alert alert-warning py-2 mb-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Le dossier <code>config/</code> n'est pas accessible en écriture. Vérifiez les permissions (WHM : propriétaire root, répertoire déployé writable).
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-3">
        <div class="card ms-card">
            <div class="card-header fw-semibold">Fichiers</div>
            <div class="list-group list-group-flush">
                <?php foreach ($definitions as $fileId => $def): ?>
                    <a href="<?= ms_e($ms_link(['page' => 'config', 'file' => $fileId])) ?>"
                       class="list-group-item list-group-item-action<?= $fileId === $active_file ? ' active' : '' ?>">
                        <?= ms_e($def['label'] ?? $fileId) ?>
                        <div class="small opacity-75"><?= ms_e($def['file'] ?? '') ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="card-footer p-2">
                <a href="<?= ms_e($dashboard_url ?? $scriptname) ?>" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard MegaStats
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <?php $activeDef = $definitions[$active_file] ?? []; ?>
        <div class="card ms-card">
            <div class="card-header fw-semibold">
                <?= ms_e($activeDef['label'] ?? $active_file) ?>
                <span class="text-secondary fw-normal small ms-2"><?= ms_e($activeDef['file'] ?? '') ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($activeDef['description'])): ?>
                    <p class="text-secondary small"><?= ms_e($activeDef['description']) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= ms_e($ms_link(['page' => 'config', 'file' => $active_file])) ?>">
                    <?= $csrf_field ?? '' ?>
                    <input type="hidden" name="config_action" value="save">
                    <input type="hidden" name="file" value="<?= ms_e($active_file) ?>">

                    <?php foreach ($fields as $field): ?>
                        <?php $renderField($field); ?>
                    <?php endforeach; ?>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-sm"<?= empty($config_writable) ? ' disabled' : '' ?>>
                            <i class="bi bi-check2 me-1"></i>Enregistrer
                        </button>
                        <a href="<?= ms_e($ms_link(['page' => 'config', 'file' => $active_file])) ?>" class="btn btn-outline-secondary btn-sm">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (empty($whm_embedded)) {
    require MEGASTATS_ROOT . '/templates/partials/footer.php';
}
?>
