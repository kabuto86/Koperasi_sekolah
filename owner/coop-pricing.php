<?php
// Harga koperasi tambahan — peringkat plan_coop_tiers per pelan (PELAN 14)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.coop_pricing');
$activeMenu = 'menu.coop_pricing';
$pageScripts = [View::asset('js/owner/coop-pricing.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-info small"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= e(t('coop_pricing.intro')) ?></div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-6">
                        <label class="visually-hidden" for="plan-select"><?= e(t('field.plan')) ?></label>
                        <select class="form-select" id="plan-select"></select>
                    </div>
                    <div class="col-sm-6 small text-body-secondary" id="plan-base-price"></div>
                </div>
            </div>
            <form id="tiers-form" novalidate>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= e(t('coop_pricing.from_coop')) ?></th>
                                <th><?= e(t('coop_pricing.to_coop')) ?></th>
                                <th><?= e(t('coop_pricing.price_monthly_each')) ?></th>
                                <th><?= e(t('coop_pricing.price_yearly_each')) ?></th>
                                <th class="text-end"><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                            </tr>
                        </thead>
                        <tbody id="tiers-body"></tbody>
                    </table>
                </div>
                <div class="card-body border-top d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-tier">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('coop_pricing.add_tier')) ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-default-tier">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> <?= e(t('coop_pricing.use_default')) ?>
                    </button>
                    <button type="submit" class="btn btn-sm btn-brand ms-auto"><?= e(t('btn.save')) ?></button>
                </div>
            </form>
        </div>
        <p class="small text-body-secondary mt-2"><?= e(t('coop_pricing.rules')) ?></p>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('coop_pricing.calculator')) ?></div>
            <div class="card-body">
                <label class="form-label" for="calc-coops"><?= e(t('coop_pricing.calc_coops')) ?></label>
                <input type="number" class="form-control mb-3" id="calc-coops" min="1" max="100" value="3">
                <dl class="row small mb-0">
                    <dt class="col-6"><?= e(t('field.price_monthly')) ?></dt><dd class="col-6 text-end fw-semibold" id="calc-monthly">—</dd>
                    <dt class="col-6"><?= e(t('field.price_yearly')) ?></dt><dd class="col-6 text-end fw-semibold mb-0" id="calc-yearly">—</dd>
                </dl>
                <div class="form-text"><?= e(t('coop_pricing.calc_help')) ?></div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
