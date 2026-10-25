<?php
// Kelas sekolah koperasi semasa — ikut darjah/tingkatan (cth. 4 Bestari)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.school_classes');
$activeMenu = 'menu.my_cooperatives';
$subNavGroup = 'cooperatives';
$subNavActive = 'vendor/classes.php';
$pageScripts = [View::asset('js/vendor/classes.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <form class="card border-0 shadow-sm" id="class-form" novalidate>
            <div class="card-header bg-body fw-semibold"><?= e(t('class.add')) ?></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="class-grade"><?= e(t('field.grade')) ?></label>
                    <select class="form-select" id="class-grade" name="grade_level_code" required></select>
                </div>
                <label class="form-label" for="class-names"><?= e(t('class.names')) ?></label>
                <textarea class="form-control" id="class-names" name="names" data-type="lines" rows="4" required placeholder="Bestari&#10;Cemerlang"></textarea>
                <div class="form-text"><?= e(t('class.names_help')) ?></div>
                <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><?= e(t('btn.add')) ?></button>
            </div>
        </form>
        <p class="small text-body-secondary mt-2"><?= e(t('class.help')) ?></p>
    </div>
    <div class="col-12 col-lg-8">
        <div class="row g-3" id="class-groups">
            <div class="col-12 text-body-secondary"><?= e(t('common.loading')) ?></div>
        </div>
    </div>
</div>

<div class="modal fade" id="rename-modal" tabindex="-1" aria-labelledby="rename-title" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form class="modal-content" id="rename-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="rename-title"><?= e(t('class.rename')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <label class="form-label" for="rename-name"><?= e(t('class.name')) ?></label>
                <input type="text" class="form-control" id="rename-name" name="class_name" required maxlength="40">
                <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
