<?php if (empty($whm_embedded)): ?>
</main>
<?php endif; ?>
<footer class="container-fluid border-top py-3 text-center text-secondary small ms-page-footer">
    MegaStats v<?= ms_e($version ?? '') ?><?php if (!empty($pagegen)): ?> · <?= ms_e($pagegen) ?><?php endif; ?>
</footer>
<?php if (empty($whm_embedded)): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($include_charts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/charts.js"></script>
<?php endif; ?>
<?php if (!empty($include_mail_js)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/mail.js"></script>
<?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/update.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/theme.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/app.js"></script>
</body>
</html>
<?php endif; ?>
