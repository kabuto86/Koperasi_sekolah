<?php
// Invois langganan vendor — senarai, butiran, bayar online melalui gateway pemilik (PELAN 14)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.invoices');
$activeMenu = 'menu.subscription';
$subNavGroup = 'billing';
$subNavActive = 'vendor/invoices.php';
$pageScripts = [View::asset('js/vendor/invoices.js')];
$invoiceStatuses = ['issued', 'paid', 'void'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="invoice-filters" role="search">
            <div class="col-12 col-md-8">
                <label class="visually-hidden" for="inv-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="inv-q" name="q" placeholder="<?= e(t('billing.invoice_search')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="inv-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="inv-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($invoiceStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('invoice.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.invoice_no')) ?></th>
                    <th><?= e(t('field.period')) ?></th>
                    <th><?= e(t('field.due_at')) ?></th>
                    <th class="text-end"><?= e(t('field.total')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="invoices-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="invoices-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('billing.invoice_help')) ?></p>

<div class="modal fade" id="invoice-modal" tabindex="-1" aria-labelledby="invoice-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="invoice-title"><?= e(t('invoice.title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <div class="small text-body-secondary"><?= e(t('invoice.bill_to')) ?></div>
                        <div class="fw-semibold" id="iv-vendor"></div>
                    </div>
                    <div class="text-end small">
                        <div id="iv-status"></div>
                        <div id="iv-dates"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><?= e(t('field.description')) ?></th>
                                <th class="text-end"><?= e(t('field.qty')) ?></th>
                                <th class="text-end"><?= e(t('field.amount')) ?></th>
                            </tr>
                        </thead>
                        <tbody id="iv-lines"></tbody>
                        <tfoot>
                            <tr><td colspan="2" class="text-end"><?= e(t('field.subtotal')) ?></td><td class="text-end" id="iv-subtotal"></td></tr>
                            <tr><td colspan="2" class="text-end" id="iv-sst-label"></td><td class="text-end" id="iv-tax"></td></tr>
                            <tr class="fw-bold"><td colspan="2" class="text-end"><?= e(t('field.total')) ?></td><td class="text-end" id="iv-total"></td></tr>
                        </tfoot>
                    </table>
                </div>
                <div id="iv-payments"></div>
                <p class="small text-body-secondary mb-0"><?= e(t('invoice.snapshot_note')) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.close')) ?></button>
                <button type="button" class="btn btn-brand d-none" id="btn-pay"><i class="bi bi-credit-card" aria-hidden="true"></i> <?= e(t('billing.pay_now')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
