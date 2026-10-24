<?php
// Langganan & invois — dua tab
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.subscriptions');
$activeMenu = 'menu.subscriptions';
$pageScripts = [View::asset('js/owner/subscriptions.js')];
$subscriptionStatuses = ['trial', 'active', 'past_due', 'suspended', 'cancelled'];
$invoiceStatuses = ['draft', 'issued', 'paid', 'void'];
require __DIR__ . '/_layout/header.php';
?>
<ul class="nav nav-tabs mb-0" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-subscriptions" data-bs-toggle="tab" data-bs-target="#pane-subscriptions" type="button"
                role="tab" aria-controls="pane-subscriptions" aria-selected="true"><?= e(t('subscription.title_plural')) ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-invoices" data-bs-toggle="tab" data-bs-target="#pane-invoices" type="button"
                role="tab" aria-controls="pane-invoices" aria-selected="false"><?= e(t('invoice.title_plural')) ?></button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-subscriptions" role="tabpanel" aria-labelledby="tab-subscriptions">
        <div class="card border-0 border-top-0 shadow-sm rounded-top-0">
            <div class="card-body border-bottom">
                <form class="row g-2" id="subscription-filters" role="search">
                    <div class="col-12 col-md-5">
                        <label class="visually-hidden" for="sub-q"><?= e(t('btn.search')) ?></label>
                        <input type="search" class="form-control" id="sub-q" name="q" placeholder="<?= e(t('vendor.search_placeholder')) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="visually-hidden" for="sub-status"><?= e(t('common.status')) ?></label>
                        <select class="form-select" id="sub-status" name="status">
                            <option value=""><?= e(t('common.all_statuses')) ?></option>
                            <?php foreach ($subscriptionStatuses as $status): ?>
                            <option value="<?= e($status) ?>"><?= e(t('vendor.status.' . $status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="visually-hidden" for="sub-cycle"><?= e(t('field.billing_cycle')) ?></label>
                        <select class="form-select" id="sub-cycle" name="billing_cycle">
                            <option value=""><?= e(t('common.all_cycles')) ?></option>
                            <option value="monthly"><?= e(t('billing.cycle.monthly')) ?></option>
                            <option value="yearly"><?= e(t('billing.cycle.yearly')) ?></option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="reset" class="btn btn-outline-secondary"><?= e(t('btn.reset')) ?></button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('menu.vendors')) ?></th>
                            <th><?= e(t('field.plan')) ?></th>
                            <th class="text-center"><?= e(t('menu.cooperatives')) ?></th>
                            <th><?= e(t('field.current_period')) ?></th>
                            <th><?= e(t('common.status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="subscriptions-body"></tbody>
                </table>
            </div>
            <div class="card-footer bg-body" id="subscriptions-pagination"></div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-invoices" role="tabpanel" aria-labelledby="tab-invoices">
        <div class="card border-0 shadow-sm rounded-top-0">
            <div class="card-body border-bottom">
                <form class="row g-2" id="invoice-filters" role="search">
                    <div class="col-12 col-md-6">
                        <label class="visually-hidden" for="inv-q"><?= e(t('btn.search')) ?></label>
                        <input type="search" class="form-control" id="inv-q" name="q" placeholder="<?= e(t('invoice.search_placeholder')) ?>">
                    </div>
                    <div class="col-8 col-md-4">
                        <label class="visually-hidden" for="inv-status"><?= e(t('common.status')) ?></label>
                        <select class="form-select" id="inv-status" name="status">
                            <option value=""><?= e(t('common.all_statuses')) ?></option>
                            <?php foreach ($invoiceStatuses as $status): ?>
                            <option value="<?= e($status) ?>"><?= e(t('invoice.status.' . $status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-2 d-grid">
                        <button type="reset" class="btn btn-outline-secondary"><?= e(t('btn.reset')) ?></button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('field.invoice_no')) ?></th>
                            <th><?= e(t('menu.vendors')) ?></th>
                            <th><?= e(t('field.period')) ?></th>
                            <th><?= e(t('field.due_at')) ?></th>
                            <th class="text-end"><?= e(t('field.total')) ?></th>
                            <th><?= e(t('common.status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="invoices-body"></tbody>
                </table>
            </div>
            <div class="card-footer bg-body" id="invoices-pagination"></div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
