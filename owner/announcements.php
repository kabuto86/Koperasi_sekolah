<?php
// Pengumuman vendor — kepada semua atau vendor terpilih; draf / terbit
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.announcements');
$activeMenu = 'menu.announcements';
$pageActions = [['id' => 'btn-add-announcement', 'label' => t('announcement.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/owner/announcements.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('announcement.title_field')) ?></th>
                    <th><?= e(t('announcement.target')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('field.created_by')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="announcements-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="announcements-pagination"></div>
</div>

<div class="modal fade" id="announcement-modal" tabindex="-1" aria-labelledby="announcement-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="announcement-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="announcement-modal-title"><?= e(t('announcement.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label" for="ann-title"><?= e(t('announcement.title_field')) ?></label>
                    <input type="text" class="form-control" id="ann-title" name="title" required maxlength="150">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ann-body"><?= e(t('announcement.body')) ?></label>
                    <textarea class="form-control" id="ann-body" name="body" rows="5" required></textarea>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <fieldset class="mb-2">
                    <legend class="form-label fs-6"><?= e(t('announcement.target')) ?></legend>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="target" id="ann-target-all" value="all" checked>
                        <label class="form-check-label" for="ann-target-all"><?= e(t('announcement.target_all')) ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="target" id="ann-target-selected" value="selected">
                        <label class="form-check-label" for="ann-target-selected"><?= e(t('announcement.target_selected')) ?></label>
                    </div>
                </fieldset>
                <div class="border rounded p-2 d-none app-scroll-box" id="ann-vendors" aria-label="<?= e(t('menu.vendors')) ?>"></div>
                <div class="text-danger small d-none" id="ann-vendors-error"><?= e(t('announcement.choose_vendor')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="button" class="btn btn-outline-secondary" id="btn-save-draft"><?= e(t('announcement.save_draft')) ?></button>
                <button type="button" class="btn btn-brand" id="btn-publish">
                    <i class="bi bi-send" aria-hidden="true"></i> <?= e(t('announcement.publish')) ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
