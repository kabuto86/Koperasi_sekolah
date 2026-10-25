<?php
// Modul add-on: Jualan murah / clearance — jualan bertempoh dengan harga khas (PELAN 5.3)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'clearance';
$pageTitle = t('module.clearance');
$activeMenu = 'module.clearance';
$pageActions = [['id' => 'btn-add-sale', 'label' => t('clearance.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/addons/clearance-form.js'), View::asset('js/vendor/addons/clearance.js')];
$saleStates = ['active', 'scheduled', 'ended', 'inactive'];
require __DIR__ . '/../_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="sale-filters" role="search">
            <div class="col-12 col-md-8">
                <label class="visually-hidden" for="sale-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="sale-q" name="q" placeholder="<?= e(t('clearance.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="sale-state"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="sale-state" name="state">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($saleStates as $state): ?>
                    <option value="<?= e($state) ?>"><?= e(t('clearance.state.' . $state)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.name')) ?></th>
                    <th><?= e(t('field.period')) ?></th>
                    <th class="text-center"><?= e(t('clearance.items')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="sales-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="sales-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('clearance.help')) ?></p>

<?php require __DIR__ . '/../_layout/addons/clearance-sale-modal.php'; ?>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
