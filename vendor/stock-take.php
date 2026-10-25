<?php
// Kiraan stock take — masukkan kuantiti sebenar, perbezaan dikira terus
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('stocktake.title');
$activeMenu = 'menu.inventory';
$backUrl = View::url('vendor/stock-takes.php');
$pageActions = [
    ['id' => 'btn-save-counts', 'label' => t('stocktake.save_counts'), 'icon' => 'bi-save', 'class' => 'btn-outline-secondary d-none'],
    ['id' => 'btn-complete', 'label' => t('stocktake.complete'), 'icon' => 'bi-check2-all', 'class' => 'btn-brand d-none'],
];
$pageScripts = [View::asset('js/vendor/stock-take.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
        <div class="small text-body-secondary"><?= e(t('common.status')) ?></div><div id="take-status">—</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
        <div class="small text-body-secondary"><?= e(t('stocktake.started')) ?></div><div class="small" id="take-started">—</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
        <div class="small text-body-secondary"><?= e(t('stocktake.progress')) ?></div><div class="fw-semibold" id="take-progress">—</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
        <div class="small text-body-secondary"><?= e(t('stocktake.variance')) ?></div><div class="fw-semibold" id="take-variance">—</div>
    </div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom row g-2">
        <div class="col-12 col-md-6">
            <label class="visually-hidden" for="take-q"><?= e(t('btn.search')) ?></label>
            <input type="search" class="form-control" id="take-q" placeholder="<?= e(t('product.search_placeholder')) ?>">
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="take-uncounted">
                <label class="form-check-label" for="take-uncounted"><?= e(t('stocktake.uncounted_only')) ?></label>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.product')) ?></th>
                    <th><?= e(t('field.category')) ?></th>
                    <th class="text-end"><?= e(t('stocktake.system_qty')) ?></th>
                    <th><?= e(t('stocktake.counted_qty')) ?></th>
                    <th class="text-end"><?= e(t('stocktake.variance')) ?></th>
                </tr>
            </thead>
            <tbody id="take-body"></tbody>
        </table>
    </div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('stocktake.keyboard_help')) ?></p>
<?php require __DIR__ . '/_layout/footer.php'; ?>
