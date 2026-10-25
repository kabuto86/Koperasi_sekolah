<?php
// Koperasi saya — senarai koperasi vendor (satu koperasi = satu sekolah)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.my_cooperatives');
$activeMenu = 'menu.my_cooperatives';
$subNavGroup = 'cooperatives';
$subNavActive = 'vendor/cooperatives.php';
$pageActions = [['id' => 'btn-add-coop', 'label' => t('coop.add'), 'icon' => 'bi-plus-lg', 'href' => View::url('vendor/cooperative-form.php')]];
$pageScripts = [View::asset('js/vendor/cooperatives.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3" id="coop-grid">
    <div class="col-12 text-body-secondary"><?= e(t('common.loading')) ?></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
