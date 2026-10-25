<?php
// Modul add-on: butiran jualan murah — item & harga khas (PELAN 9.H clearance_items)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'clearance';
$pageTitle = t('clearance.sale_detail');
$activeMenu = 'module.clearance';
$backUrl = View::url('vendor/addons/clearance.php');
$pageActions = [
    ['id' => 'btn-edit-sale', 'label' => t('clearance.edit'), 'icon' => 'bi-pencil', 'class' => 'btn-outline-secondary'],
    ['id' => 'btn-add-item', 'label' => t('clearance.add_item'), 'icon' => 'bi-plus-lg'],
];
$pageScripts = [View::asset('js/vendor/addons/clearance-form.js'), View::asset('js/vendor/addons/clearance-sale.js')];
require __DIR__ . '/../_layout/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <i class="bi bi-percent fs-2 text-danger" aria-hidden="true"></i>
        <div class="flex-grow-1 min-w-0">
            <h2 class="h5 mb-1" id="sale-name">—</h2>
            <div class="small text-body-secondary"><i class="bi bi-calendar-range" aria-hidden="true"></i> <span id="sale-period">—</span></div>
        </div>
        <div id="sale-state"></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body fw-semibold"><?= e(t('clearance.items')) ?></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.product')) ?></th>
                    <th class="text-end"><?= e(t('clearance.regular_price')) ?></th>
                    <th class="text-end"><?= e(t('clearance.sale_price')) ?></th>
                    <th class="text-center"><?= e(t('clearance.qty_limit')) ?></th>
                    <th class="text-center"><?= e(t('clearance.available')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="items-body"></tbody>
        </table>
    </div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('clearance.items_help')) ?></p>

<?php require __DIR__ . '/../_layout/addons/clearance-sale-modal.php'; ?>

<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="item-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="item-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="item-modal-title"><?= e(t('clearance.add_item')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="item-product"><?= e(t('field.product')) ?></label>
                    <select class="form-select" id="item-product" name="product_id" required>
                        <option value=""><?= e(t('common.choose')) ?></option>
                    </select>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12 d-none" id="item-variant-box">
                    <label class="form-label" for="item-variant"><?= e(t('clearance.variant')) ?></label>
                    <select class="form-select" id="item-variant" name="variant_id">
                        <option value=""><?= e(t('clearance.all_variants')) ?></option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="item-price"><?= e(t('clearance.sale_price')) ?></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">RM</span>
                        <input type="text" inputmode="decimal" class="form-control" id="item-price" name="sale_price" data-type="money" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="form-text" id="item-regular"></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="item-limit"><?= e(t('clearance.qty_limit')) ?></label>
                    <input type="number" class="form-control" id="item-limit" name="qty_limit" data-type="int" min="1" placeholder="<?= e(t('plan.unlimited')) ?>">
                    <div class="form-text"><?= e(t('clearance.qty_limit_help')) ?></div>
                    <div class="invalid-feedback"><?= e(t('error.invalid')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
