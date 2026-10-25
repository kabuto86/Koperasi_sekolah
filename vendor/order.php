<?php
// Butiran pesanan — item ikut anak, siapa ambil, bayaran, sejarah status & serahan, tukar status
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('order.detail_title');
$activeMenu = 'menu.orders';
$backUrl = View::url('vendor/orders.php');
$pageActions = [
    ['id' => 'btn-label', 'label' => t('order.bag_labels'), 'icon' => 'bi-tags', 'class' => 'btn-outline-secondary', 'href' => '#'],
    ['id' => 'btn-handover', 'label' => t('menu.handover'), 'icon' => 'bi-hand-index', 'class' => 'btn-outline-secondary d-none', 'href' => '#'],
];
$pageScripts = [View::asset('js/vendor/order-common.js'), View::asset('js/vendor/order.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3" id="order-detail">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                <div class="app-short-code" id="ord-short">—</div>
                <div class="flex-grow-1">
                    <div class="fw-semibold" id="ord-no">—</div>
                    <div class="small text-body-secondary" id="ord-created"></div>
                </div>
                <div id="ord-status"></div>
            </div>
        </div>
        <div id="ord-groups"></div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 fw-normal"><?= e(t('field.subtotal')) ?></dt><dd class="col-6 text-end" id="ord-subtotal">—</dd>
                    <dt class="col-6 fw-normal"><?= e(t('order.discount')) ?></dt><dd class="col-6 text-end" id="ord-discount">—</dd>
                    <dt class="col-6 fw-normal d-none js-tax-row" id="ord-tax-label"><?= e(t('field.tax')) ?></dt><dd class="col-6 text-end d-none js-tax-row" id="ord-tax">—</dd>
                    <dt class="col-6"><?= e(t('field.total')) ?></dt><dd class="col-6 text-end fw-bold mb-0" id="ord-total">—</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('order.change_status')) ?></div>
            <div class="card-body d-grid gap-2" id="status-actions"></div>
            <div class="card-footer bg-body small text-body-secondary"><?= e(t('order.status_help')) ?></div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('order.parent')) ?></div>
            <div class="card-body small">
                <div class="fw-semibold" id="ord-customer">—</div>
                <div id="ord-phone"></div>
                <div class="text-break" id="ord-email"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('order.payment_pickup')) ?></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 fw-normal text-body-secondary"><?= e(t('order.payment')) ?></dt><dd class="col-7" id="ord-payment"></dd>
                    <dt class="col-5 fw-normal text-body-secondary"><?= e(t('order.pickup_by')) ?></dt><dd class="col-7" id="ord-pickup-by">—</dd>
                    <dt class="col-5 fw-normal text-body-secondary d-none" id="ord-slot-label"><?= e(t('store.slot.title')) ?></dt><dd class="col-7 d-none" id="ord-slot">—</dd>
                    <dt class="col-5 fw-normal text-body-secondary"><?= e(t('order.pickup_code')) ?></dt><dd class="col-7" id="ord-code">—</dd>
                    <dt class="col-5 fw-normal text-body-secondary"><?= e(t('order.expires_at')) ?></dt><dd class="col-7 mb-0" id="ord-expires">—</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><?= e(t('order.history')) ?></div>
            <ul class="list-unstyled small mb-0 p-3 app-timeline" id="ord-logs"></ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('order.pickups')) ?></div>
            <ul class="list-group list-group-flush small" id="ord-pickups"></ul>
        </div>
    </div>
</div>

<div class="modal fade" id="cancel-modal" tabindex="-1" aria-labelledby="cancel-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="cancel-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cancel-title"><?= e(t('order.cancel')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary"><?= e(t('order.cancel_help')) ?></p>
                <label class="form-label" for="cancel-note"><?= e(t('order.cancel_reason')) ?></label>
                <textarea class="form-control" id="cancel-note" name="note" rows="2" required></textarea>
                <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.back')) ?></button>
                <button type="submit" class="btn btn-danger"><?= e(t('order.cancel')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
