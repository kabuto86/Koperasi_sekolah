<?php
// Laporan — jualan harian/bulanan, ikut produk/darjah/kelas, untung, stok, tunai vs online + CSV (PELAN 5.2 modul 15)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.reports');
$activeMenu = 'menu.reports';
$pageActions = [
    ['id' => 'btn-csv', 'label' => t('btn.export_csv'), 'icon' => 'bi-filetype-csv', 'class' => 'btn-outline-secondary'],
    ['id' => 'btn-print', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-outline-secondary'],
];
$pageScripts = [View::asset('js/vendor/reports.js')];
$reportGroups = [
    'report.group.sales'    => ['daily', 'monthly', 'payment'],
    'report.group.products' => ['product', 'profit', 'stock'],
    'report.group.students' => ['grade', 'class'],
];
$quickRanges = ['today', '7days', 'this_month', 'last_month'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body">
        <form class="row g-2 align-items-end" id="report-form" novalidate>
            <div class="col-12 col-lg-4">
                <label class="form-label small" for="rep-type"><?= e(t('report.type_label')) ?></label>
                <select class="form-select" id="rep-type" name="type">
                    <?php foreach ($reportGroups as $groupKey => $types): ?>
                    <optgroup label="<?= e(t($groupKey)) ?>">
                        <?php foreach ($types as $type): ?>
                        <option value="<?= e($type) ?>"><?= e(t('report.type.' . $type)) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="rep-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="rep-from" name="date_from">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="rep-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="rep-to" name="date_to">
            </div>
            <div class="col-12 col-lg-2 d-grid">
                <button type="submit" class="btn btn-brand"><i class="bi bi-bar-chart" aria-hidden="true"></i> <?= e(t('report.run')) ?></button>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap gap-1" id="rep-ranges">
                    <?php foreach ($quickRanges as $range): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-range="<?= e($range) ?>"><?= e(t('report.range.' . $range)) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="report-result" class="d-none">
    <div class="mb-3">
        <h2 class="h5 mb-0" id="rep-title"></h2>
        <div class="small text-body-secondary" id="rep-meta"></div>
    </div>
    <div class="card border-0 shadow-sm mb-3 d-print-none" id="rep-chart-card">
        <div class="card-body" id="rep-chart"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr id="rep-head"></tr></thead>
                <tbody id="rep-body"></tbody>
                <tfoot class="table-light fw-semibold"><tr id="rep-foot"></tr></tfoot>
            </table>
        </div>
    </div>
    <p class="small text-body-secondary mt-2" id="rep-note"></p>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
