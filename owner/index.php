<?php
// Papan pemuka owner — MRR, vendor ikut status, koperasi, pesanan, langganan hampir tamat
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.dashboard');
$activeMenu = 'menu.dashboard';
$pageScripts = [View::asset('js/owner/dashboard.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('dashboard.mrr'), 'icon' => 'bi-graph-up', 'valueId' => 'stat-mrr', 'subId' => 'stat-mrr-sub']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('dashboard.active_vendors'), 'icon' => 'bi-shop', 'valueId' => 'stat-vendors', 'subId' => 'stat-vendors-sub', 'tone' => 'success']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('menu.cooperatives'), 'icon' => 'bi-building', 'valueId' => 'stat-coops', 'subId' => 'stat-coops-sub', 'tone' => 'info']); ?>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('dashboard.orders_this_month'), 'icon' => 'bi-bag-check', 'valueId' => 'stat-orders', 'subId' => 'stat-orders-sub', 'tone' => 'warning']); ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><?= e(t('dashboard.mrr_chart')) ?></div>
            <div class="card-body" id="mrr-chart"><?= e(t('common.loading')) ?></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><?= e(t('dashboard.vendor_status')) ?></div>
            <ul class="list-group list-group-flush" id="vendor-status-list"></ul>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= e(t('dashboard.expiring')) ?></span>
        <a class="small" href="<?= e(View::url('owner/subscriptions.php')) ?>"><?= e(t('common.view_all')) ?></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('menu.vendors')) ?></th>
                    <th><?= e(t('field.plan')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('field.period_end')) ?></th>
                </tr>
            </thead>
            <tbody id="expiring-body"></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
