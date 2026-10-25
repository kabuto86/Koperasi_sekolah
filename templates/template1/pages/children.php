<?php // Anak saya: tambah / sunting (nama, sekolah, darjah, kelas, tahun) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><?= e(t('store.nav.children')) ?></h1>
    <button class="btn btn-brand" type="button" id="child-add"><i class="bi bi-person-plus" aria-hidden="true"></i> <?= e(t('store.children.add')) ?></button>
</div>
<p class="small text-body-secondary"><?= e(t('store.children.help')) ?></p>
<div class="row g-3" id="child-list"></div>

<div class="modal fade" id="child-modal" tabindex="-1" aria-labelledby="child-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="child-form" novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="child-modal-title"><?= e(t('store.children.add')) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" data-type="int">
                    <div class="mb-3">
                        <label class="form-label" for="child-name"><?= e(t('store.children.name')) ?></label>
                        <input class="form-control" type="text" id="child-name" name="full_name" maxlength="100" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="child-school"><?= e(t('field.school')) ?></label>
                        <select class="form-select" id="child-school" name="school_slug" required>
                            <option value=""><?= e(t('common.choose')) ?></option>
                        </select>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="child-grade"><?= e(t('field.grade')) ?></label>
                            <select class="form-select" id="child-grade" name="grade_level_code" required>
                                <option value=""><?= e(t('common.choose')) ?></option>
                            </select>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="child-class"><?= e(t('field.class')) ?></label>
                            <input class="form-control" type="text" id="child-class" name="class_name" list="child-class-options" maxlength="50" required>
                            <datalist id="child-class-options"></datalist>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="child-year"><?= e(t('kit.school_year')) ?></label>
                            <select class="form-select" id="child-year" name="school_year" data-type="int" required></select>
                            <div class="form-text"><?= e(t('store.children.year_help')) ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                    <button class="btn btn-brand" type="submit"><?= e(t('btn.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
