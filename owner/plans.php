<?php
// Pelan langganan — CRUD, harga bulanan & tahunan, had
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.plans');
$activeMenu = 'menu.plans';
$pageActions = [['id' => 'btn-add-plan', 'label' => t('plan.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/owner/plans.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.plan')) ?></th>
                    <th class="text-end"><?= e(t('field.price_monthly')) ?></th>
                    <th class="text-end"><?= e(t('field.price_yearly')) ?></th>
                    <th><?= e(t('plan.limits')) ?></th>
                    <th class="text-center"><?= e(t('menu.vendors')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="plans-body"></tbody>
        </table>
    </div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('plan.limit_help')) ?></p>

<div class="modal fade" id="plan-modal" tabindex="-1" aria-labelledby="plan-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="plan-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="plan-modal-title"><?= e(t('plan.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="plan-code"><?= e(t('field.code')) ?></label>
                        <input type="text" class="form-control text-uppercase" id="plan-code" name="code" required maxlength="20" pattern="[A-Za-z0-9_]+">
                        <div class="invalid-feedback"><?= e(t('error.code_format')) ?></div>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label" for="plan-name"><?= e(t('field.name')) ?></label>
                        <input type="text" class="form-control" id="plan-name" name="name" required maxlength="60">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="plan-monthly"><?= e(t('field.price_monthly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="plan-monthly" name="price_monthly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="plan-yearly"><?= e(t('field.price_yearly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="plan-yearly" name="price_yearly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                        <div class="form-text" id="plan-yearly-hint"></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="plan-max-coops"><?= e(t('plan.max_cooperatives')) ?></label>
                        <input type="number" class="form-control" id="plan-max-coops" name="max_cooperatives" data-type="int" min="1">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="plan-max-products"><?= e(t('plan.max_products')) ?></label>
                        <input type="number" class="form-control" id="plan-max-products" name="max_products" data-type="int" min="1">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="plan-max-staff"><?= e(t('plan.max_staff')) ?></label>
                        <input type="number" class="form-control" id="plan-max-staff" name="max_staff" data-type="int" min="1">
                    </div>
                    <div class="col-12">
                        <div class="form-text mt-0"><?= e(t('plan.unlimited_help')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="plan-features"><?= e(t('plan.features')) ?></label>
                        <textarea class="form-control" id="plan-features" name="features" data-type="lines" rows="3"></textarea>
                        <div class="form-text"><?= e(t('plan.features_help')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="plan-sort"><?= e(t('field.sort_order')) ?></label>
                        <input type="number" class="form-control" id="plan-sort" name="sort_order" data-type="int" min="0" value="0">
                    </div>
                    <div class="col-sm-8 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="plan-active" name="is_active" checked>
                            <label class="form-check-label" for="plan-active"><?= e(t('plan.active_help')) ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
