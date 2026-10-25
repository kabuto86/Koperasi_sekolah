<?php
// Picking list ikut kelas — halaman cetak (PELAN 5.2 modul 7)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('order.picking_list');
$activeMenu = 'menu.orders';
$backUrl = View::url('vendor/orders.php');
$pageActions = [['id' => 'btn-print', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-brand d-print-none']];
$extraCss = [View::asset('css/print-label.css')];
$pageScripts = [View::asset('js/vendor/order-common.js'), View::asset('js/vendor/picking-list.js')];
require __DIR__ . '/_layout/header.php';
?>
<form class="row g-2 mb-3 d-print-none" id="pick-filters">
    <div class="col-6 col-md-3">
        <label class="form-label small" for="pick-grade"><?= e(t('field.grade')) ?></label>
        <select class="form-select" id="pick-grade" name="grade"><option value=""><?= e(t('common.all_grades')) ?></option></select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small" for="pick-class"><?= e(t('field.class')) ?></label>
        <select class="form-select" id="pick-class" name="class_name"><option value=""><?= e(t('order.all_classes')) ?></option></select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small" for="pick-status"><?= e(t('order.pick_scope')) ?></label>
        <select class="form-select" id="pick-status" name="status">
            <option value="paid,unpaid_cash,preparing"><?= e(t('order.pick_scope_to_prepare')) ?></option>
            <option value="ready,partially_collected"><?= e(t('order.pick_scope_ready')) ?></option>
        </select>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom pb-2 mb-3">
            <div>
                <div class="fw-bold fs-5" id="pick-school">—</div>
                <div class="small" id="pick-scope"></div>
            </div>
            <div class="small text-end" id="pick-meta"></div>
        </div>
        <div id="pick-groups"></div>
        <div class="app-print-break" id="pick-totals-section">
            <h2 class="h6 mt-4"><?= e(t('order.pick_totals')) ?></h2>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th><?= e(t('field.product')) ?></th>
                        <th><?= e(t('product.sku')) ?></th>
                        <th class="text-end"><?= e(t('field.qty')) ?></th>
                        <th class="text-center"><?= e(t('order.picked')) ?></th>
                    </tr>
                </thead>
                <tbody id="pick-totals"></tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
