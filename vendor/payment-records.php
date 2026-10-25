<?php
// Rekod bayaran koperasi semasa — online (gateway) & tunai (kaunter)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.payment_records');
$activeMenu = 'menu.payments';
$subNavGroup = 'payments';
$subNavActive = 'vendor/payment-records.php';
$pageScripts = [View::asset('js/vendor/payment-records.js')];
$recordStatuses = ['paid', 'pending', 'failed', 'refunded'];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('payment.online_total'), 'icon' => 'bi-globe', 'valueId' => 'sum-online', 'tone' => 'brand']); ?>
    </div>
    <div class="col-6 col-lg-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('payment.cash_total'), 'icon' => 'bi-cash-coin', 'valueId' => 'sum-cash', 'tone' => 'success']); ?>
    </div>
    <div class="col-6 col-lg-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('payment.refunded_total'), 'icon' => 'bi-arrow-return-left', 'valueId' => 'sum-refunded', 'tone' => 'danger']); ?>
    </div>
    <div class="col-6 col-lg-3">
        <?php View::partial('partials/stat-card.php', ['label' => t('payment.pending_count'), 'icon' => 'bi-hourglass-split', 'valueId' => 'sum-pending', 'tone' => 'warning']); ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="record-filters" role="search">
            <div class="col-12 col-lg-4">
                <label class="visually-hidden" for="rec-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="rec-q" name="q" placeholder="<?= e(t('payment.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="rec-method"><?= e(t('field.payment_method')) ?></label>
                <select class="form-select" id="rec-method" name="method">
                    <option value=""><?= e(t('order.all_methods')) ?></option>
                    <option value="online"><?= e(t('payment.method.online')) ?></option>
                    <option value="cash"><?= e(t('payment.method.cash')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="rec-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="rec-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($recordStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('payment.record.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="rec-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="rec-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="rec-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="rec-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.time')) ?></th>
                    <th><?= e(t('order.order')) ?></th>
                    <th><?= e(t('field.payment_method')) ?></th>
                    <th><?= e(t('field.reference')) ?></th>
                    <th class="text-end"><?= e(t('field.amount')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                </tr>
            </thead>
            <tbody id="records-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="records-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><i class="bi bi-shield-check" aria-hidden="true"></i> <?= e(t('payment.records_help')) ?></p>
<?php require __DIR__ . '/_layout/footer.php'; ?>
