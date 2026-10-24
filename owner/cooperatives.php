<?php
// Semua koperasi semua vendor — baca sahaja
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.cooperatives');
$activeMenu = 'menu.cooperatives';
$pageScripts = [View::asset('js/owner/cooperatives.js')];
$coopStatuses = ['active', 'suspended', 'closed'];
$schoolTypes = ['SK', 'SJKC', 'SJKT', 'SMK', 'SMKA', 'SBP', 'other'];
require __DIR__ . '/_layout/header.php';
?>
<p class="text-body-secondary small"><i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('coop.read_only_note')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="coop-filters" role="search">
            <div class="col-12 col-lg-4">
                <label class="visually-hidden" for="filter-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="filter-q" name="q" placeholder="<?= e(t('coop.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="visually-hidden" for="filter-vendor"><?= e(t('menu.vendors')) ?></label>
                <select class="form-select" id="filter-vendor" name="vendor_id">
                    <option value=""><?= e(t('common.all_vendors')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="filter-type"><?= e(t('field.school_type')) ?></label>
                <select class="form-select" id="filter-type" name="school_type">
                    <option value=""><?= e(t('common.all_types')) ?></option>
                    <?php foreach ($schoolTypes as $type): ?>
                    <option value="<?= e($type) ?>"><?= e(t('school_type.' . $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="filter-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="filter-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($coopStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e(t('coop.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-1 d-grid">
                <button type="reset" class="btn btn-outline-secondary" title="<?= e(t('btn.reset')) ?>">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="d-lg-none"> <?= e(t('btn.reset')) ?></span>
                </button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.school')) ?></th>
                    <th><?= e(t('menu.vendors')) ?></th>
                    <th><?= e(t('field.location')) ?></th>
                    <th><?= e(t('field.template')) ?></th>
                    <th><?= e(t('field.modules')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                </tr>
            </thead>
            <tbody id="coops-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="coops-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
