<?php
// Kit keperluan sekolah — satu kit per darjah/tingkatan per tahun persekolahan
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.school_kits');
$activeMenu = 'menu.school_kits';
$pageActions = [['id' => 'btn-copy-year', 'label' => t('kit.copy_previous'), 'icon' => 'bi-copy', 'class' => 'btn-outline-secondary']];
$pageScripts = [View::asset('js/vendor/school-kits.js')];
$currentYear = (int) date('Y');
$schoolYears = [$currentYear, $currentYear + 1];
require __DIR__ . '/_layout/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <label class="form-label mb-0" for="kit-year"><?= e(t('kit.school_year')) ?></label>
    <select class="form-select w-auto" id="kit-year">
        <?php foreach ($schoolYears as $year): ?>
        <option value="<?= e($year) ?>"<?= $year === $currentYear + 1 ? ' selected' : '' ?>><?= e($year) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="small text-body-secondary"><?= e(t('kit.year_help')) ?></span>
</div>
<div class="row g-3" id="kit-grid">
    <div class="col-12 text-body-secondary"><?= e(t('common.loading')) ?></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
