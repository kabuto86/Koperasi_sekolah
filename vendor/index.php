<?php
// Papan pemuka vendor — jualan hari ini, pesanan, stok rendah, pengumuman (ikut koperasi semasa)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.dashboard');
$activeMenu = 'menu.dashboard';
$pageScripts = [View::asset('js/vendor/dashboard.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="text-body-secondary small mb-3" id="current-coop"><?= e(t('common.loading')) ?></p>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('vendor_dashboard.sales_today'), 'icon' => 'bi-cash-stack', 'valueId' => 'stat-sales', 'subId' => 'stat-sales-sub']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('vendor_dashboard.to_prepare'), 'icon' => 'bi-box2', 'valueId' => 'stat-prepare', 'subId' => 'stat-prepare-sub', 'tone' => 'warning']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('vendor_dashboard.ready'), 'icon' => 'bi-bag-check', 'valueId' => 'stat-ready', 'subId' => 'stat-ready-sub', 'tone' => 'success']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('vendor_dashboard.low_stock'), 'icon' => 'bi-exclamation-triangle', 'valueId' => 'stat-low', 'subId' => 'stat-low-sub', 'tone' => 'danger']); ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><?= e(t('vendor_dashboard.sales_7days')) ?></div>
            <div class="card-body" id="sales-chart"><?= e(t('common.loading')) ?></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-megaphone" aria-hidden="true"></i> <?= e(t('vendor_dashboard.announcements')) ?></div>
            <ul class="list-group list-group-flush" id="announcement-list"></ul>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= e(t('vendor_dashboard.low_stock_list')) ?></span>
        <a class="small" href="<?= e(View::url('vendor/inventory.php?low_only=1')) ?>"><?= e(t('common.view_all')) ?></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.product')) ?></th>
                    <th class="text-end"><?= e(t('inventory.available')) ?></th>
                    <th class="text-end"><?= e(t('inventory.min_stock')) ?></th>
                    <th class="text-end"><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody id="low-stock-body"></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
