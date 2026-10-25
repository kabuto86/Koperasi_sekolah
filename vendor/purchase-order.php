<?php
// Pesanan belian — draf (boleh sunting) → dipesan → terima barang (stok masuk automatik)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = isset($_GET['id']) ? t('po.title') : t('po.create');
$activeMenu = 'menu.suppliers';
$backUrl = View::url('vendor/purchase-orders.php');
$pageActions = [
    ['id' => 'btn-cancel-po', 'label' => t('po.cancel'), 'icon' => 'bi-x-circle', 'class' => 'btn-outline-danger d-none'],
    ['id' => 'btn-print-po', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-outline-secondary d-none d-print-none'],
];
$pageScripts = [View::asset('js/vendor/purchase-order.js')];
require __DIR__ . '/_layout/header.php';
?>
<!-- Mod sunting (baharu / draf) -->
<form class="d-none" id="po-form" novalidate>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label" for="po-supplier"><?= e(t('field.supplier')) ?></label>
                <select class="form-select" id="po-supplier" name="supplier_id" required>
                    <option value=""><?= e(t('common.choose')) ?></option>
                </select>
                <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="po-note"><?= e(t('field.note')) ?></label>
                <input type="text" class="form-control" id="po-note" name="note" maxlength="200">
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-body fw-semibold"><?= e(t('po.items')) ?></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= e(t('field.product')) ?></th>
                        <th><?= e(t('field.qty')) ?></th>
                        <th><?= e(t('po.unit_cost')) ?></th>
                        <th class="text-end"><?= e(t('field.amount')) ?></th>
                        <th><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                    </tr>
                </thead>
                <tbody id="po-lines"></tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end"><?= e(t('field.total')) ?></th><td class="text-end fw-bold" id="po-total">RM 0.00</td><td></td></tr>
                </tfoot>
            </table>
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-line">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('po.add_line')) ?>
            </button>
            <div class="text-danger small mt-2 d-none" id="po-lines-error"><?= e(t('po.items_required')) ?></div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" id="btn-save-draft"><?= e(t('announcement.save_draft')) ?></button>
        <button type="button" class="btn btn-brand" id="btn-submit-po"><i class="bi bi-send" aria-hidden="true"></i> <?= e(t('po.submit')) ?></button>
    </div>
</form>

<!-- Mod lihat / terima -->
<div class="d-none" id="po-view">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
            <div class="col-sm-6 col-lg-3"><div class="small text-body-secondary"><?= e(t('field.supplier')) ?></div><div class="fw-semibold" id="view-supplier"></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-body-secondary"><?= e(t('common.status')) ?></div><div id="view-status"></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-body-secondary"><?= e(t('po.ordered_at')) ?></div><div id="view-ordered"></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-body-secondary"><?= e(t('field.note')) ?></div><div id="view-note"></div></div>
        </div>
    </div>
    <form class="card border-0 shadow-sm mb-3" id="receive-form" novalidate>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= e(t('field.product')) ?></th>
                        <th class="text-end"><?= e(t('po.qty_ordered')) ?></th>
                        <th class="text-end"><?= e(t('po.qty_received')) ?></th>
                        <th class="text-end"><?= e(t('po.unit_cost')) ?></th>
                        <th class="text-end"><?= e(t('field.amount')) ?></th>
                        <th class="js-receive-col"><?= e(t('po.receive_now')) ?></th>
                    </tr>
                </thead>
                <tbody id="view-lines"></tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-end"><?= e(t('field.total')) ?></th><td class="text-end fw-bold" id="view-total"></td><td class="js-receive-col"></td></tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer bg-body d-flex flex-wrap justify-content-between align-items-center gap-2 js-receive-col">
            <span class="small text-body-secondary"><?= e(t('po.receive_help')) ?></span>
            <button type="submit" class="btn btn-success"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> <?= e(t('po.receive')) ?></button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
