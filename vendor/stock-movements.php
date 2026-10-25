<?php
// Sejarah pergerakan stok koperasi semasa (baca sahaja)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.stock_movements');
$activeMenu = 'menu.inventory';
$subNavGroup = 'inventory';
$subNavActive = 'vendor/stock-movements.php';
$pageScripts = [View::asset('js/vendor/stock-movements.js')];
$movementTypes = ['sale', 'pos_sale', 'reserve', 'release', 'collect', 'refund', 'restock', 'adjust', 'stocktake', 'po_receive'];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2 d-none" id="product-filter-note">
    <i class="bi bi-funnel" aria-hidden="true"></i>
    <span><?= e(t('movement.filtered_by_product')) ?></span>
    <a class="ms-auto small" href="<?= e(View::url('vendor/stock-movements.php')) ?>"><?= e(t('movement.clear_product_filter')) ?></a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="movement-filters" role="search">
            <div class="col-12 col-lg-4">
                <label class="visually-hidden" for="mv-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="mv-q" name="q" placeholder="<?= e(t('movement.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-sm-4 col-lg-3">
                <label class="visually-hidden" for="mv-type"><?= e(t('movement.type_label')) ?></label>
                <select class="form-select" id="mv-type" name="type">
                    <option value=""><?= e(t('movement.all_types')) ?></option>
                    <?php foreach ($movementTypes as $type): ?>
                    <option value="<?= e($type) ?>"><?= e(t('movement.type.' . $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="mv-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="mv-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="mv-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="mv-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
            <div class="col-12 col-lg-1 d-grid">
                <button type="reset" class="btn btn-outline-secondary" title="<?= e(t('btn.reset')) ?>">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="d-lg-none"> <?= e(t('btn.reset')) ?></span>
                </button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.time')) ?></th>
                    <th><?= e(t('field.product')) ?></th>
                    <th><?= e(t('movement.type_label')) ?></th>
                    <th class="text-end"><?= e(t('movement.change')) ?></th>
                    <th class="text-end"><?= e(t('movement.balance')) ?></th>
                    <th><?= e(t('movement.reference')) ?></th>
                    <th><?= e(t('movement.by')) ?></th>
                </tr>
            </thead>
            <tbody id="movements-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="movements-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
