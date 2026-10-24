<?php
// Darjah & tingkatan — senarai master (PRA, D1–D6, T1–T6) dan nama 4 bahasa
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.grades');
$activeMenu = 'menu.grades';
$pageScripts = [View::asset('js/owner/grades.js')];
$languages = Config::get('languages');
$levelTypes = ['preschool', 'primary', 'secondary'];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><?= e(t('grade.intro')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.code')) ?></th>
                    <?php foreach ($languages as $code): ?>
                    <th lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></th>
                    <?php endforeach; ?>
                    <th><?= e(t('grade.level_type')) ?></th>
                    <th class="text-center"><?= e(t('field.sort_order')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="grades-body"></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="grade-modal" tabindex="-1" aria-labelledby="grade-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="grade-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="grade-modal-title"><?= e(t('grade.edit')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="grade-code"><?= e(t('field.code')) ?></label>
                        <input type="text" class="form-control font-monospace" id="grade-code" name="code" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="grade-sort"><?= e(t('field.sort_order')) ?></label>
                        <input type="number" class="form-control" id="grade-sort" name="sort_order" data-type="int" min="0" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="grade-level"><?= e(t('grade.level_type')) ?></label>
                        <select class="form-select" id="grade-level" name="level_type" required>
                            <?php foreach ($levelTypes as $type): ?>
                            <option value="<?= e($type) ?>"><?= e(t('grade.level.' . $type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach ($languages as $code): ?>
                    <div class="col-sm-6">
                        <label class="form-label" for="grade-name-<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></label>
                        <input type="text" class="form-control" id="grade-name-<?= e($code) ?>" name="names.<?= e($code) ?>"
                               lang="<?= e($code) ?>" required maxlength="40">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <?php endforeach; ?>
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
