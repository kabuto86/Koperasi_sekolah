<?php
// Modul add-on: Berita & pengumuman — pos kepada ibu bapa koperasi ini, dipapar di store + e-mel pilihan (PELAN 5.3)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'news';
$pageTitle = t('module.news');
$activeMenu = 'module.news';
$pageActions = [['id' => 'btn-add-news', 'label' => t('news.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/addons/news.js')];
$newsStates = ['published', 'scheduled', 'draft'];
require __DIR__ . '/../_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="news-filters" role="search">
            <div class="col-12 col-md-8">
                <label class="visually-hidden" for="news-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="news-q" name="q" placeholder="<?= e(t('news.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="news-state"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="news-state" name="state">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <?php foreach ($newsStates as $state): ?>
                    <option value="<?= e($state) ?>"><?= e(t('news.state.' . $state)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('news.title')) ?></th>
                    <th><?= e(t('news.published_at')) ?></th>
                    <th><?= e(t('news.email')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="news-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="news-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('news.help')) ?></p>

<div class="modal fade" id="news-modal" tabindex="-1" aria-labelledby="news-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="news-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="news-modal-title"><?= e(t('news.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12 col-md-8">
                    <div class="mb-3">
                        <label class="form-label" for="news-title"><?= e(t('news.title')) ?></label>
                        <input type="text" class="form-control" id="news-title" name="title" required maxlength="150">
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div>
                        <label class="form-label" for="news-body-input"><?= e(t('news.body')) ?></label>
                        <textarea class="form-control" id="news-body-input" name="body" rows="7" required maxlength="5000"></textarea>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-4 text-center">
                    <div class="form-label text-start"><?= e(t('news.image')) ?></div>
                    <div class="app-product-image mx-auto mb-2" id="news-image-preview"><i class="bi bi-image" aria-hidden="true"></i></div>
                    <label class="btn btn-sm btn-outline-secondary" for="news-image-file">
                        <i class="bi bi-upload" aria-hidden="true"></i> <?= e(t('upload.choose_image')) ?>
                    </label>
                    <input type="file" class="visually-hidden" id="news-image-file" accept="image/png,image/jpeg,image/webp">
                    <button type="button" class="btn btn-sm btn-link text-danger" id="news-image-remove"><?= e(t('upload.remove')) ?></button>
                    <div class="form-text"><?= e(t('upload.image_help')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="news-published"><?= e(t('news.published_at')) ?></label>
                    <input type="datetime-local" class="form-control" id="news-published" name="published_at" required>
                    <div class="form-text"><?= e(t('news.schedule_help')) ?></div>
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="form-check form-switch mt-sm-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="news-active" name="is_active">
                        <label class="form-check-label" for="news-active"><?= e(t('news.publish')) ?></label>
                    </div>
                    <div class="form-text"><?= e(t('news.publish_help')) ?></div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 bg-body-tertiary">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="news-email" name="send_email">
                            <label class="form-check-label fw-semibold" for="news-email"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e(t('news.send_email')) ?></label>
                        </div>
                        <div class="form-text" id="news-email-help"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="news-preview-modal" tabindex="-1" aria-labelledby="news-preview-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="news-preview-title"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="small text-body-secondary mb-2" id="news-preview-date"></div>
                <div class="mb-3 d-none" id="news-preview-image"></div>
                <p class="mb-0 app-pre-line" id="news-preview-body"></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
