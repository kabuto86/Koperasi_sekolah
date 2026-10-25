<?php
// Pemulangan & refund — pulang barang, pulang stok, rekod refund (PELAN 5.2 modul 12)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.refunds');
$activeMenu = 'menu.refunds';
$pageActions = [['id' => 'btn-new-refund', 'label' => t('refund.create'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/refunds.js')];
$refundMethods = ['cash', 'bank_transfer', 'gateway'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="refund-filters" role="search">
            <div class="col-12 col-lg-5">
                <label class="visually-hidden" for="ref-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="ref-q" name="q" placeholder="<?= e(t('refund.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-sm-4 col-lg-3">
                <label class="visually-hidden" for="ref-method"><?= e(t('refund.method')) ?></label>
                <select class="form-select" id="ref-method" name="method">
                    <option value=""><?= e(t('order.all_methods')) ?></option>
                    <?php foreach ($refundMethods as $method): ?>
                    <option value="<?= e($method) ?>"><?= e(t('refund.method.' . $method)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="ref-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="ref-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="ref-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="ref-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('refund.no')) ?></th>
                    <th><?= e(t('order.order')) ?></th>
                    <th class="text-end"><?= e(t('field.amount')) ?></th>
                    <th><?= e(t('refund.method')) ?></th>
                    <th><?= e(t('refund.items')) ?></th>
                    <th><?= e(t('refund.reason')) ?></th>
                    <th><?= e(t('movement.by')) ?></th>
                </tr>
            </thead>
            <tbody id="refunds-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="refunds-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('refund.help')) ?></p>

<div class="modal fade" id="refund-modal" tabindex="-1" aria-labelledby="refund-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="refund-title"><?= e(t('refund.create')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <form class="row g-2 mb-3" id="refund-lookup" role="search" novalidate>
                    <div class="col">
                        <label class="visually-hidden" for="lookup-q"><?= e(t('refund.find_order')) ?></label>
                        <input type="search" class="form-control" id="lookup-q" name="q" required placeholder="<?= e(t('refund.find_placeholder')) ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search" aria-hidden="true"></i> <?= e(t('btn.search')) ?></button>
                    </div>
                </form>

                <form class="d-none" id="refund-form" novalidate>
                    <div class="d-flex flex-wrap justify-content-between gap-2 border rounded p-2 mb-3">
                        <div>
                            <div class="fw-semibold" id="rf-order-no"></div>
                            <div class="small text-body-secondary" id="rf-order-meta"></div>
                        </div>
                        <div class="text-end small">
                            <div><?= e(t('field.total')) ?>: <span class="fw-semibold" id="rf-total"></span></div>
                            <div><?= e(t('refund.already_refunded')) ?>: <span id="rf-refunded"></span></div>
                            <div><?= e(t('refund.refundable')) ?>: <span class="fw-semibold text-success" id="rf-refundable"></span></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= e(t('field.product')) ?></th>
                                    <th class="text-end"><?= e(t('field.unit_price')) ?></th>
                                    <th class="text-center"><?= e(t('refund.collected_of')) ?></th>
                                    <th class="text-end"><?= e(t('refund.return_qty')) ?></th>
                                </tr>
                            </thead>
                            <tbody id="rf-items"></tbody>
                        </table>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rf-restock" name="restock">
                        <label class="form-check-label" for="rf-restock"><?= e(t('refund.restock')) ?></label>
                        <div class="form-text"><?= e(t('refund.restock_help')) ?></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="rf-amount"><?= e(t('refund.amount')) ?></label>
                            <div class="input-group">
                                <span class="input-group-text">RM</span>
                                <input type="text" inputmode="decimal" class="form-control" id="rf-amount" name="amount" data-type="money" required>
                            </div>
                            <div class="form-text"><?= e(t('refund.amount_help')) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="rf-method"><?= e(t('refund.method')) ?></label>
                            <select class="form-select" id="rf-method" name="method" required>
                                <?php foreach ($refundMethods as $method): ?>
                                <option value="<?= e($method) ?>"><?= e(t('refund.method.' . $method)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="rf-method-help"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="rf-reason"><?= e(t('refund.reason')) ?></label>
                            <textarea class="form-control" id="rf-reason" name="reason" rows="2" required maxlength="255"></textarea>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-danger" form="refund-form" id="rf-submit" disabled><?= e(t('refund.confirm')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
