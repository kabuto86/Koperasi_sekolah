<?php
// Borang kit sekolah — item, kuantiti, wajib/pilihan, nota, susunan; draf / terbit
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('kit.title');
$activeMenu = 'menu.school_kits';
$backUrl = View::url('vendor/school-kits.php');
$pageActions = [['id' => 'btn-delete-kit', 'label' => t('btn.delete'), 'icon' => 'bi-trash', 'class' => 'btn-outline-danger d-none']];
$pageScripts = [View::asset('js/vendor/school-kit.js')];
require __DIR__ . '/_layout/header.php';
?>
<form id="kit-form" novalidate>
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="kit-grade"><?= e(t('field.grade')) ?></label>
                        <select class="form-select" id="kit-grade" name="grade_level_code" required></select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="kit-year"><?= e(t('kit.school_year')) ?></label>
                        <input type="number" class="form-control" id="kit-year" name="school_year" data-type="int" readonly>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label" for="kit-name"><?= e(t('kit.name')) ?></label>
                        <input type="text" class="form-control" id="kit-name" name="name" maxlength="80" placeholder="<?= e(t('kit.name_placeholder')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="kit-note"><?= e(t('kit.note')) ?></label>
                        <textarea class="form-control" id="kit-note" name="note" rows="2"></textarea>
                        <div class="form-text"><?= e(t('kit.note_help')) ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><?= e(t('kit.items')) ?></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><span class="visually-hidden"><?= e(t('field.sort_order')) ?></span></th>
                                <th><?= e(t('field.product')) ?></th>
                                <th><?= e(t('field.qty')) ?></th>
                                <th><?= e(t('kit.mandatory')) ?></th>
                                <th><?= e(t('field.note')) ?></th>
                                <th class="text-end"><?= e(t('field.amount')) ?></th>
                                <th><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                            </tr>
                        </thead>
                        <tbody id="kit-items"></tbody>
                    </table>
                </div>
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-item">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('kit.add_item')) ?>
                    </button>
                    <div class="text-end small">
                        <div><?= e(t('kit.total_all')) ?>: <span class="fw-bold" id="kit-total">RM 0.00</span></div>
                        <div class="text-body-secondary"><?= e(t('kit.total_mandatory')) ?>: <span id="kit-mandatory">RM 0.00</span></div>
                    </div>
                </div>
                <div class="px-3 pb-3 text-danger small d-none" id="kit-items-error"><?= e(t('kit.items_required')) ?></div>
            </div>
            <p class="small text-body-secondary"><?= e(t('kit.help')) ?></p>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span><?= e(t('common.status')) ?></span>
                    <span class="badge text-bg-light border" id="kit-status"><?= e(t('kit.draft')) ?></span>
                </div>
                <div class="card-footer bg-body d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="btn-save-draft"><?= e(t('kit.save_draft')) ?></button>
                    <button type="button" class="btn btn-brand" id="btn-publish"><i class="bi bi-send" aria-hidden="true"></i> <?= e(t('kit.publish')) ?></button>
                    <button type="button" class="btn btn-outline-secondary d-none" id="btn-unpublish"><?= e(t('kit.unpublish')) ?></button>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body">
                    <div class="fw-semibold"><i class="bi bi-phone" aria-hidden="true"></i> <?= e(t('kit.parent_preview')) ?></div>
                    <div class="small text-body-secondary"><?= e(t('kit.parent_preview_help')) ?></div>
                </div>
                <ul class="list-group list-group-flush small" id="kit-preview"></ul>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/_layout/footer.php'; ?>
