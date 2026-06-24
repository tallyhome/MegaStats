<?php if (empty($whm_embedded)): ?>
</main>
<footer class="container-fluid border-top py-3 text-center text-secondary small">
    MegaStats <?= ms_e($version ?? '') ?> · <?= ms_e($pagegen ?? '') ?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($include_charts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/charts.js"></script>
<?php endif; ?>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/theme.js"></script>
<script src="<?= ms_e($assets_base ?? '/assets') ?>/js/app.js"></script>
</body>
</html>
<?php endif; ?>
