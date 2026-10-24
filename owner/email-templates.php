<?php
// Templat e-mel — per jenis × per bahasa, pemegang tempat {nama}, pratonton
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.email_templates');
$activeMenu = 'menu.email_templates';
$pageScripts = [View::asset('js/owner/email-templates.js')];
$languages = Config::get('languages');
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= e(t('email_template.types')) ?></div>
            <div class="list-group list-group-flush" id="template-list"></div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <form class="card border-0 shadow-sm" id="email-form" novalidate>
            <div class="card-header bg-body">
                <div class="fw-semibold" id="editor-title"><?= e(t('email_template.choose')) ?></div>
                <div class="btn-group btn-group-sm mt-2" role="group" aria-label="<?= e(t('lang.label')) ?>">
                    <?php foreach ($languages as $index => $code): ?>
                    <input type="radio" class="btn-check" name="language_code" id="email-lang-<?= e($code) ?>" value="<?= e($code) ?>"
                           autocomplete="off"<?= $index === 0 ? ' checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="email-lang-<?= e($code) ?>" lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" name="template_key" id="email-key">
                <div class="alert alert-warning small d-none" id="email-missing"><?= e(t('email_template.missing_language')) ?></div>
                <div class="mb-3">
                    <label class="form-label" for="email-subject"><?= e(t('email_template.subject')) ?></label>
                    <input type="text" class="form-control" id="email-subject" name="subject" required maxlength="150">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="email-body"><?= e(t('email_template.body')) ?></label>
                    <textarea class="form-control font-monospace small" id="email-body" name="body_html" rows="12" required></textarea>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="small text-body-secondary mb-1"><?= e(t('email_template.placeholders_help')) ?></div>
                <div class="d-flex flex-wrap gap-1" id="placeholder-list"></div>
            </div>
            <div class="card-footer bg-body d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="btn-preview">
                    <i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('template.preview')) ?>
                </button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="email-preview-modal" tabindex="-1" aria-labelledby="email-preview-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="email-preview-title"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-4x3 border rounded">
                    <iframe id="email-preview-frame" sandbox="" title="<?= e(t('template.preview')) ?>"></iframe>
                </div>
                <div class="form-text"><?= e(t('email_template.preview_note')) ?></div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
