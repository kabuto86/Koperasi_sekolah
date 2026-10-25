<?php
// Stock take — senarai sesi kiraan stok koperasi semasa
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.stock_takes');
$activeMenu = 'menu.inventory';
$subNavGroup = 'inventory';
$subNavActive = 'vendor/stock-takes.php';
$pageActions = [['id' => 'btn-start-take', 'label' => t('stocktake.start'), 'icon' => 'bi-clipboard-plus']];
$pageScripts = [View::asset('js/vendor/stock-takes.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><?= e(t('stocktake.intro')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('stocktake.started')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-center"><?= e(t('stocktake.progress')) ?></th>
                    <th class="text-end"><?= e(t('stocktake.variance')) ?></th>
                    <th><?= e(t('stocktake.completed')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="takes-body"></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
