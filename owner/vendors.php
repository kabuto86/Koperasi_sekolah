<?php
// Senarai vendor — carian, tapis status & pelan
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.vendors');
$activeMenu = 'menu.vendors';
$pageScripts = [View::asset('js/owner/vendors.js')];
$vendorStatuses = ['trial', 'active', 'past_due', 'suspended', 'cancelled'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="vendor-filters" role="search">
            <div class="col-12 col-md-5">
                <label class="visually-hidden" for="filter-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="filter-q" name="q" placeholder="<?= e(t('vendor.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="filter-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="filter-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($vendorStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('vendor.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="visually-hidden" for="filter-plan"><?= e(t('field.plan')) ?></label>
                <select class="form-select" id="filter-plan" name="plan_id">
                    <option value=""><?= e(t('common.all_plans')) ?></option>
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
                    <th><?= e(t('field.company')) ?></th>
                    <th><?= e(t('field.contact')) ?></th>
                    <th><?= e(t('field.plan')) ?></th>
                    <th class="text-center"><?= e(t('menu.cooperatives')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('field.period_end')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="vendors-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="vendors-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
