<?php
// Borang jualan murah (nama, tempoh, aktif) — dipakai clearance.php & clearance-sale.php
?>
<div class="modal fade" id="sale-modal" tabindex="-1" aria-labelledby="sale-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="sale-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="sale-modal-title"><?= e(t('clearance.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="sale-name"><?= e(t('field.name')) ?></label>
                    <input type="text" class="form-control" id="sale-name" name="name" required maxlength="100" placeholder="<?= e(t('clearance.name_placeholder')) ?>">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="sale-start"><?= e(t('promo.starts_on')) ?></label>
                    <input type="date" class="form-control" id="sale-start" name="starts_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="sale-end"><?= e(t('promo.ends_on')) ?></label>
                    <input type="date" class="form-control" id="sale-end" name="ends_on" required>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="sale-active" name="is_active">
                        <label class="form-check-label" for="sale-active"><?= e(t('common.active')) ?></label>
                    </div>
                    <div class="form-text"><?= e(t('clearance.active_help')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
