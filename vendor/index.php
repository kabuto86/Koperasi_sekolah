<?php
// Papan pemuka vendor — kandungan dibina dalam U3a
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.dashboard');
$activeMenu = 'menu.dashboard';
$pageScripts = [View::asset('js/vendor-dashboard.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="text-body-secondary" id="current-coop"><?= e(t('common.loading')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-body-secondary py-5">
        <i class="bi bi-speedometer2 display-6 d-block mb-2" aria-hidden="true"></i>
        <?= e(t('common.coming_soon')) ?>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
