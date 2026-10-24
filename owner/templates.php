<?php
// Template frontend store ibu bapa — senarai, pratonton, harga (Template 1 percuma)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.templates');
$activeMenu = 'menu.templates';
$pageScripts = [View::asset('js/owner/templates.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><?= e(t('template.intro')) ?></p>
<div class="row g-3" id="templates-grid">
    <div class="col-12 text-body-secondary"><?= e(t('common.loading')) ?></div>
</div>

<div class="modal fade" id="preview-modal" tabindex="-1" aria-labelledby="preview-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="preview-title"><?= e(t('template.preview')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="app-template-preview app-template-preview-lg mb-3" id="preview-image"></div>
                <p class="mb-1" id="preview-description"></p>
                <p class="small text-body-secondary mb-0" id="preview-folder"></p>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-secondary" id="preview-open" href="#" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> <?= e(t('template.open_store_preview')) ?>
                </a>
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal"><?= e(t('btn.close')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="template-modal" tabindex="-1" aria-labelledby="template-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="template-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="template-modal-title"><?= e(t('template.edit')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label" for="template-code"><?= e(t('field.code')) ?></label>
                    <input type="text" class="form-control font-monospace" id="template-code" name="code" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="template-name"><?= e(t('field.name')) ?></label>
                    <input type="text" class="form-control" id="template-name" name="name" required maxlength="80">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="template-description"><?= e(t('field.description')) ?></label>
                    <textarea class="form-control" id="template-description" name="description" rows="2"></textarea>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="template-free" name="is_free">
                    <label class="form-check-label" for="template-free"><?= e(t('template.is_free')) ?></label>
                </div>
                <div class="row g-3 mb-3" id="template-prices">
                    <div class="col-6">
                        <label class="form-label" for="template-monthly"><?= e(t('field.price_monthly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="template-monthly" name="price_monthly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="template-yearly"><?= e(t('field.price_yearly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="template-yearly" name="price_yearly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="col-12 form-text mt-1"><?= e(t('template.price_per_coop')) ?></div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="template-active" name="is_active">
                    <label class="form-check-label" for="template-active"><?= e(t('template.active_help')) ?></label>
                </div>
                <div class="form-text d-none" id="template1-note"><?= e(t('template.template1_locked')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
