<?php
// Langganan & bil — pelan semasa, kitaran, koperasi dilanggan, modul & template, anggaran pembaharuan (PELAN 14)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.subscription');
$activeMenu = 'menu.subscription';
$subNavGroup = 'billing';
$subNavActive = 'vendor/subscription.php';
$pageScripts = [View::asset('js/vendor/subscription.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 d-none" id="unpaid-alert" role="status">
    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
    <span class="flex-grow-1" id="unpaid-text"></span>
    <a class="btn btn-sm btn-warning" href="<?= e(View::url('vendor/invoices.php?status=issued')) ?>"><?= e(t('billing.view_invoices')) ?></a>
</div>
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 d-none" id="pending-alert" role="status">
    <i class="bi bi-calendar-check" aria-hidden="true"></i>
    <span class="flex-grow-1" id="pending-text"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-change"><?= e(t('billing.cancel_change')) ?></button>
</div>

<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 d-none" id="no-plan-alert" role="status">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    <span class="flex-grow-1"><strong><?= e(t('billing.no_plan_title')) ?></strong> — <?= e(t('billing.no_plan_help')) ?></span>
    <button type="button" class="btn btn-sm btn-brand" id="btn-choose-plan"><?= e(t('billing.change_plan')) ?></button>
</div>

<div id="sub-content">
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="small text-body-secondary"><?= e(t('billing.current_plan')) ?></div>
                        <div class="fs-4 fw-bold" id="sub-plan">—</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span id="sub-status"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-change-plan"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> <?= e(t('billing.change_plan')) ?></button>
                    </div>
                </div>
                <dl class="row small mb-2">
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary"><?= e(t('field.billing_cycle')) ?></dt>
                    <dd class="col-7 col-sm-8 mb-1" id="sub-cycle"></dd>
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary"><?= e(t('field.current_period')) ?></dt>
                    <dd class="col-7 col-sm-8 mb-1" id="sub-period"></dd>
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary"><?= e(t('billing.plan_price')) ?></dt>
                    <dd class="col-7 col-sm-8 mb-1" id="sub-price"></dd>
                </dl>
                <ul class="list-unstyled small mb-0" id="sub-features"></ul>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><?= e(t('billing.usage')) ?></div>
            <div class="card-body" id="sub-usage"></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold"><?= e(t('billing.subscribed_coops')) ?></span>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(View::url('vendor/addon-store.php')) ?>"><i class="bi bi-puzzle" aria-hidden="true"></i> <?= e(t('menu.addon_store')) ?></a>
                    <a class="btn btn-sm btn-brand" href="<?= e(View::url('vendor/cooperative-form.php')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('coop.add')) ?></a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= e(t('common.cooperative')) ?></th>
                            <th><?= e(t('field.template')) ?></th>
                            <th><?= e(t('field.modules')) ?></th>
                            <th class="text-end"><?= e(t('billing.price_per_cycle')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="coops-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body">
                <div class="fw-semibold"><?= e(t('billing.renewal_estimate')) ?></div>
                <div class="small text-body-secondary" id="renewal-date"></div>
            </div>
            <ul class="list-group list-group-flush" id="renewal-lines"></ul>
            <div class="card-footer bg-body small">
                <div class="d-flex justify-content-between"><span><?= e(t('field.subtotal')) ?></span><span id="renewal-subtotal"></span></div>
                <div class="d-flex justify-content-between"><span id="renewal-sst-label"></span><span id="renewal-tax"></span></div>
                <div class="d-flex justify-content-between fw-bold fs-6"><span><?= e(t('field.total')) ?></span><span id="renewal-total"></span></div>
                <div class="text-body-secondary mt-2"><?= e(t('billing.renewal_help')) ?></div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="plan-modal" tabindex="-1" aria-labelledby="plan-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="plan-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="plan-title"><?= e(t('billing.change_plan')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <fieldset class="mb-3">
                    <legend class="form-label fs-6"><?= e(t('field.billing_cycle')) ?></legend>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="billing_cycle" id="cycle-monthly" value="monthly" required>
                        <label class="btn btn-outline-secondary" for="cycle-monthly"><?= e(t('billing.cycle.monthly')) ?></label>
                        <input type="radio" class="btn-check" name="billing_cycle" id="cycle-yearly" value="yearly">
                        <label class="btn btn-outline-secondary" for="cycle-yearly"><?= e(t('billing.cycle.yearly')) ?></label>
                    </div>
                </fieldset>
                <div class="row g-2" id="plan-options"></div>
                <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= e(t('billing.change_help')) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
