<?php
// Pesanan online koperasi semasa — tapis, tindakan pukal, picking list, label beg
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.orders');
$activeMenu = 'menu.orders';
$pageActions = [
    ['id' => 'btn-picking', 'label' => t('order.picking_list'), 'icon' => 'bi-list-check', 'class' => 'btn-outline-secondary', 'href' => View::url('vendor/picking-list.php')],
    ['id' => 'btn-labels', 'label' => t('order.bag_labels'), 'icon' => 'bi-tags', 'class' => 'btn-outline-secondary', 'href' => View::url('vendor/bag-labels.php')],
];
$pageScripts = [View::asset('js/vendor/order-common.js'), View::asset('js/vendor/orders.js')];
$orderStatuses = ['pending_payment', 'paid', 'unpaid_cash', 'preparing', 'ready', 'partially_collected', 'collected', 'cancelled', 'expired'];
require __DIR__ . '/_layout/header.php';
?>
<div class="d-flex flex-nowrap overflow-auto gap-2 mb-3 pb-1" id="status-pills" role="group" aria-label="<?= e(t('common.status')) ?>"></div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="order-filters" role="search">
            <div class="col-12 col-lg-3">
                <label class="visually-hidden" for="ord-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="ord-q" name="q" placeholder="<?= e(t('order.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="ord-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="ord-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($orderStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('order.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="ord-grade"><?= e(t('field.grade')) ?></label>
                <select class="form-select" id="ord-grade" name="grade">
                    <option value=""><?= e(t('common.all_grades')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="visually-hidden" for="ord-class"><?= e(t('field.class')) ?></label>
                <select class="form-select" id="ord-class" name="class_name">
                    <option value=""><?= e(t('order.all_classes')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="ord-method"><?= e(t('field.payment_method')) ?></label>
                <select class="form-select" id="ord-method" name="payment_method">
                    <option value=""><?= e(t('order.all_methods')) ?></option>
                    <option value="cash"><?= e(t('payment.method.cash')) ?></option>
                    <option value="online"><?= e(t('payment.method.online')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="visually-hidden" for="ord-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="ord-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-lg-1">
                <label class="visually-hidden" for="ord-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="ord-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
            <div class="col-12 col-lg-4 js-slot-filter d-none">
                <label class="visually-hidden" for="ord-slot"><?= e(t('store.slot.title')) ?></label>
                <select class="form-select" id="ord-slot" name="pickup_slot_id">
                    <option value=""><?= e(t('order.all_slots')) ?></option>
                </select>
            </div>
        </form>
    </div>
    <div class="card-body border-bottom py-2 d-flex flex-wrap align-items-center gap-2 d-none" id="bulk-bar">
        <span class="small" id="bulk-count"></span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bulk="preparing"><?= e(t('order.mark_as', ['status' => t('order.status.preparing')])) ?></button>
        <button type="button" class="btn btn-sm btn-outline-success" data-bulk="ready"><?= e(t('order.mark_as', ['status' => t('order.status.ready')])) ?></button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><input class="form-check-input" type="checkbox" id="select-all" aria-label="<?= e(t('order.select_all')) ?>"></th>
                    <th><?= e(t('order.order')) ?></th>
                    <th><?= e(t('order.children')) ?></th>
                    <th><?= e(t('order.parent')) ?></th>
                    <th class="text-end"><?= e(t('field.total')) ?></th>
                    <th><?= e(t('order.payment')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                </tr>
            </thead>
            <tbody id="orders-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="orders-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
