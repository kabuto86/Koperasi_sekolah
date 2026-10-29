<?php
// Butiran invois — baris, cukai, bayaran; terbit / tanda dibayar / batal / cetak
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('invoice.title');
$activeMenu = 'menu.subscriptions';
$backUrl = View::url('owner/subscriptions.php#invoices');
$pageActions = [
    ['id' => 'btn-print', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-outline-secondary d-print-none'],
    ['id' => 'btn-void', 'label' => t('invoice.void'), 'icon' => 'bi-x-circle', 'class' => 'btn-outline-danger d-none d-print-none'],
    ['id' => 'btn-issue', 'label' => t('invoice.issue'), 'icon' => 'bi-send', 'class' => 'btn-brand d-none d-print-none'],
    ['id' => 'btn-mark-paid', 'label' => t('invoice.mark_paid'), 'icon' => 'bi-cash-coin', 'class' => 'btn-success d-none d-print-none'],
];
// owner_staff: baca & cetak sahaja (terbit / batal / tanda dibayar → 403 di pelayan)
if (PanelRole::current('owner') !== 'owner') {
    $pageActions = array_slice($pageActions, 0, 1);
}
$pageScripts = [View::asset('js/owner/invoice.js')];
$paymentMethods = ['bank_transfer', 'cash', 'cheque', 'other'];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-danger d-none" id="invoice-error" role="alert"></div>

<div class="card border-0 shadow-sm" id="invoice-card">
    <div class="card-body p-4">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="small text-body-secondary"><?= e(t('invoice.bill_to')) ?></div>
                <div class="fw-semibold" id="inv-vendor-name">—</div>
                <div class="small" id="inv-vendor-reg"></div>
                <div class="small" id="inv-vendor-address"></div>
                <div class="small" id="inv-vendor-email"></div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="fs-5 fw-bold" id="inv-no">—</div>
                <div id="inv-status" class="mb-2"></div>
                <dl class="row small mb-0">
                    <dt class="col-6 col-sm-7"><?= e(t('field.period')) ?></dt><dd class="col-6 col-sm-5" id="inv-period">—</dd>
                    <dt class="col-6 col-sm-7"><?= e(t('field.issued_at')) ?></dt><dd class="col-6 col-sm-5" id="inv-issued">—</dd>
                    <dt class="col-6 col-sm-7"><?= e(t('field.due_at')) ?></dt><dd class="col-6 col-sm-5" id="inv-due">—</dd>
                    <dt class="col-6 col-sm-7"><?= e(t('field.paid_at')) ?></dt><dd class="col-6 col-sm-5 mb-0" id="inv-paid">—</dd>
                </dl>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?= e(t('field.description')) ?></th>
                        <th class="text-center"><?= e(t('field.qty')) ?></th>
                        <th class="text-end"><?= e(t('field.unit_price')) ?></th>
                        <th class="text-end"><?= e(t('field.amount')) ?></th>
                    </tr>
                </thead>
                <tbody id="lines-body"></tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end fw-normal"><?= e(t('field.subtotal')) ?></th><td class="text-end" id="inv-subtotal">—</td></tr>
                    <tr><th colspan="3" class="text-end fw-normal" id="inv-tax-label"><?= e(t('field.tax')) ?></th><td class="text-end" id="inv-tax">—</td></tr>
                    <tr><th colspan="3" class="text-end"><?= e(t('field.total')) ?></th><td class="text-end fw-bold" id="inv-total">—</td></tr>
                </tfoot>
            </table>
        </div>
        <p class="small text-body-secondary"><?= e(t('invoice.snapshot_note')) ?></p>

        <h2 class="h6 mt-4"><?= e(t('invoice.payments')) ?></h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('field.paid_at')) ?></th>
                        <th><?= e(t('field.payment_method')) ?></th>
                        <th><?= e(t('field.reference')) ?></th>
                        <th class="text-end"><?= e(t('field.amount')) ?></th>
                    </tr>
                </thead>
                <tbody id="payments-body"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="paid-modal" tabindex="-1" aria-labelledby="paid-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="paid-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="paid-title"><?= e(t('invoice.mark_paid')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary" id="paid-summary"></p>
                <div class="mb-3">
                    <label class="form-label" for="paid-method"><?= e(t('field.payment_method')) ?></label>
                    <select class="form-select" id="paid-method" name="method" required>
                        <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= e($method) ?>"><?= e(t('payment.method.' . $method)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="paid-reference"><?= e(t('field.reference')) ?></label>
                    <input type="text" class="form-control" id="paid-reference" name="reference" required maxlength="80">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="paid-date"><?= e(t('field.paid_at')) ?></label>
                    <input type="date" class="form-control" id="paid-date" name="paid_at" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div>
                    <label class="form-label" for="paid-note"><?= e(t('field.note')) ?></label>
                    <textarea class="form-control" id="paid-note" name="note" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-success"><?= e(t('invoice.mark_paid')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
