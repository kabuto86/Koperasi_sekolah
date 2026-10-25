<?php
// Inventori — stok koperasi semasa, stok masuk, pelarasan, stok rendah
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.stock_levels');
$activeMenu = 'menu.inventory';
$subNavGroup = 'inventory';
$subNavActive = 'vendor/inventory.php';
$pageScripts = [View::asset('js/vendor/inventory.js')];
$adjustReasons = ['count_correction', 'damaged', 'lost', 'found', 'other'];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-warning d-flex align-items-center gap-2 d-none" id="low-alert" role="status">
    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span id="low-alert-text"></span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="inventory-filters" role="search">
            <div class="col-12 col-md-5">
                <label class="visually-hidden" for="inv-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="inv-q" name="q" placeholder="<?= e(t('product.search_placeholder')) ?>">
            </div>
            <div class="col-7 col-md-4">
                <label class="visually-hidden" for="inv-category"><?= e(t('field.category')) ?></label>
                <select class="form-select" id="inv-category" name="category_id">
                    <option value=""><?= e(t('common.all_categories')) ?></option>
                </select>
            </div>
            <div class="col-5 col-md-3 d-flex align-items-center">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="inv-low" name="low_only">
                    <label class="form-check-label" for="inv-low"><?= e(t('inventory.low_only')) ?></label>
                </div>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.product')) ?></th>
                    <th><?= e(t('field.category')) ?></th>
                    <th class="text-end"><?= e(t('inventory.on_hand')) ?></th>
                    <th class="text-end"><?= e(t('inventory.reserved')) ?></th>
                    <th class="text-end"><?= e(t('inventory.available')) ?></th>
                    <th class="text-end"><?= e(t('inventory.min_stock')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="inventory-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="inventory-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('inventory.help')) ?></p>

<div class="modal fade" id="stock-in-modal" tabindex="-1" aria-labelledby="stock-in-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="stock-in-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="stock-in-title"><?= e(t('inventory.stock_in')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-1 js-item-name"></p>
                <p class="small text-body-secondary js-item-stock"></p>
                <div class="mb-3">
                    <label class="form-label" for="stock-in-qty"><?= e(t('field.qty')) ?></label>
                    <input type="number" class="form-control" id="stock-in-qty" name="qty" data-type="int" min="1" required>
                    <div class="invalid-feedback"><?= e(t('inventory.qty_positive')) ?></div>
                </div>
                <label class="form-label" for="stock-in-note"><?= e(t('field.note')) ?></label>
                <input type="text" class="form-control" id="stock-in-note" name="note" maxlength="150" placeholder="<?= e(t('inventory.stock_in_note_placeholder')) ?>">
                <div class="form-text"><?= e(t('inventory.stock_in_help')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('inventory.stock_in')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="adjust-modal" tabindex="-1" aria-labelledby="adjust-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="adjust-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="adjust-title"><?= e(t('inventory.adjust')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-1 js-item-name"></p>
                <p class="small text-body-secondary js-item-stock"></p>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="adjust-qty"><?= e(t('inventory.actual_qty')) ?></label>
                        <input type="number" class="form-control" id="adjust-qty" name="new_qty" data-type="int" min="0" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        <div class="form-text" id="adjust-change"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="adjust-min"><?= e(t('inventory.min_stock')) ?></label>
                        <input type="number" class="form-control" id="adjust-min" name="min_stock" data-type="int" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="adjust-reason"><?= e(t('inventory.reason_label')) ?></label>
                    <select class="form-select" id="adjust-reason" name="reason" required>
                        <option value=""><?= e(t('common.choose')) ?></option>
                        <?php foreach ($adjustReasons as $reason): ?>
                        <option value="<?= e($reason) ?>"><?= e(t('inventory.reason.' . $reason)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <label class="form-label" for="adjust-note"><?= e(t('field.note')) ?></label>
                <input type="text" class="form-control" id="adjust-note" name="note" maxlength="150">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
