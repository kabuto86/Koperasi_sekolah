<?php
// Papan pemuka owner — kandungan dibina dalam U2
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.dashboard');
$activeMenu = 'menu.dashboard';
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-body-secondary py-5">
        <i class="bi bi-speedometer2 display-6 d-block mb-2" aria-hidden="true"></i>
        <?= e(t('common.coming_soon')) ?>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
