<?php
// Modul add-on: Laporan lanjutan — perbandingan tahun (bulanan & produk) dan analisis kit sekolah (W4, PELAN 5.3).
// Data: vendor/advanced-report/run (ModuleAccess advanced_report). Pemapar jadual/carta & CSV sama seperti vendor/reports.php.
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'advanced_report';
$pageTitle = t('module.advanced_report');
$activeMenu = 'module.advanced_report';
$pageActions = [
    ['id' => 'btn-csv', 'label' => t('btn.export_csv'), 'icon' => 'bi-filetype-csv', 'class' => 'btn-outline-secondary'],
    ['id' => 'btn-print', 'label' => t('btn.print'), 'icon' => 'bi-printer', 'class' => 'btn-outline-secondary'],
];
$pageScripts = [View::asset('js/vendor/addons/advanced_report.js')];
$reportGroups = [
    'advreport.group.compare' => ['year_compare', 'product_compare'],
    'advreport.group.kits'    => ['kit_summary', 'kit_items'],
];
$thisYear = (int) date('Y');
$years = range($thisYear + 1, RunAdvancedReportJob::MIN_YEAR);
require __DIR__ . '/../_layout/header.php';
?>
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body">
        <form class="row g-2 align-items-end" id="adv-form" novalidate>
            <div class="col-12 col-md-6 col-lg-5">
                <label class="form-label small" for="adv-type"><?= e(t('report.type_label')) ?></label>
                <select class="form-select" id="adv-type" name="type">
                    <?php foreach ($reportGroups as $groupKey => $types): ?>
                    <optgroup label="<?= e(t($groupKey)) ?>">
                        <?php foreach ($types as $type): ?>
                        <option value="<?= e($type) ?>"><?= e(t('advreport.type.' . $type)) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label small" for="adv-year"><?= e(t('advreport.year')) ?></label>
                <select class="form-select" id="adv-year" name="year" data-type="int">
                    <?php foreach ($years as $year): ?>
                    <option value="<?= e($year) ?>"<?= $year === $thisYear ? ' selected' : '' ?>><?= e($year) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2 d-grid">
                <button type="submit" class="btn btn-brand"><i class="bi bi-bar-chart" aria-hidden="true"></i> <?= e(t('report.run')) ?></button>
            </div>
        </form>
    </div>
</div>

<div id="adv-result" class="d-none">
    <div class="mb-3">
        <h2 class="h5 mb-0" id="adv-title"></h2>
        <div class="small text-body-secondary" id="adv-meta"></div>
    </div>
    <div class="card border-0 shadow-sm mb-3 d-print-none" id="adv-chart-card">
        <div class="card-body" id="adv-chart"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr id="adv-head"></tr></thead>
                <tbody id="adv-body"></tbody>
                <tfoot class="table-light fw-semibold"><tr id="adv-foot"></tr></tfoot>
            </table>
        </div>
    </div>
    <p class="small text-body-secondary mt-2" id="adv-note"></p>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
