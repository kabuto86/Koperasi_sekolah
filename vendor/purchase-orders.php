<?php
// Pesanan belian (PO) koperasi semasa
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.purchase_orders');
$activeMenu = 'menu.suppliers';
$subNavGroup = 'suppliers';
$subNavActive = 'vendor/purchase-orders.php';
$pageActions = [['id' => 'btn-add-po', 'label' => t('po.create'), 'icon' => 'bi-plus-lg', 'href' => View::url('vendor/purchase-order.php')]];
$pageScripts = [View::asset('js/vendor/purchase-orders.js')];
$poStatuses = ['draft', 'ordered', 'partial', 'received', 'cancelled'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="po-filters" role="search">
            <div class="col-12 col-md-5">
                <label class="visually-hidden" for="po-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="po-q" name="q" placeholder="<?= e(t('po.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="visually-hidden" for="po-supplier"><?= e(t('field.supplier')) ?></label>
                <select class="form-select" id="po-supplier" name="supplier_id">
                    <option value=""><?= e(t('po.all_suppliers')) ?></option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="po-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="po-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($poStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('po.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('po.no')) ?></th>
                    <th><?= e(t('field.supplier')) ?></th>
                    <th class="text-center"><?= e(t('po.lines')) ?></th>
                    <th class="text-end"><?= e(t('field.total')) ?></th>
                    <th><?= e(t('po.ordered_at')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                </tr>
            </thead>
            <tbody id="po-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="po-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
