<?php
// Katalog modul add-on — CRUD, nama & keterangan 4 bahasa, harga bulanan/tahunan, aktif
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.modules');
$activeMenu = 'menu.modules';
$pageActions = [['id' => 'btn-add-module', 'label' => t('module.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/owner/modules.js')];
$languages = Config::get('languages');
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.module')) ?></th>
                    <th class="text-end"><?= e(t('field.price_monthly')) ?></th>
                    <th class="text-end"><?= e(t('field.price_yearly')) ?></th>
                    <th class="text-center"><?= e(t('module.subscribers')) ?></th>
                    <th><?= e(t('module.translations')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="modules-body"></tbody>
        </table>
    </div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('module.new_module_help')) ?></p>

<div class="modal fade" id="module-modal" tabindex="-1" aria-labelledby="module-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="module-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="module-modal-title"><?= e(t('module.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="module-code"><?= e(t('field.code')) ?></label>
                        <input type="text" class="form-control font-monospace" id="module-code" name="code" required maxlength="30" pattern="[a-z][a-z0-9_]+">
                        <div class="invalid-feedback"><?= e(t('module.code_format')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="module-monthly"><?= e(t('field.price_monthly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="module-monthly" name="price_monthly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="module-yearly"><?= e(t('field.price_yearly')) ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">RM</span>
                            <input type="number" class="form-control" id="module-yearly" name="price_yearly" data-type="money" min="0" step="0.01" required>
                            <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($languages as $index => $code): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link<?= $index === 0 ? ' active' : '' ?>" id="module-tab-<?= e($code) ?>" data-bs-toggle="tab"
                                        data-bs-target="#module-pane-<?= e($code) ?>" type="button" role="tab"
                                        aria-controls="module-pane-<?= e($code) ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                                        lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tab-content border border-top-0 rounded-bottom p-3">
                            <?php foreach ($languages as $index => $code): ?>
                            <div class="tab-pane fade<?= $index === 0 ? ' show active' : '' ?>" id="module-pane-<?= e($code) ?>" role="tabpanel"
                                 aria-labelledby="module-tab-<?= e($code) ?>">
                                <div class="mb-3">
                                    <label class="form-label" for="module-name-<?= e($code) ?>"><?= e(t('field.name')) ?></label>
                                    <input type="text" class="form-control" id="module-name-<?= e($code) ?>" name="names.<?= e($code) ?>"
                                           maxlength="80" lang="<?= e($code) ?>"<?= $code === 'ms' ? ' required' : '' ?>>
                                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                                </div>
                                <label class="form-label" for="module-desc-<?= e($code) ?>"><?= e(t('field.description')) ?></label>
                                <textarea class="form-control" id="module-desc-<?= e($code) ?>" name="descriptions.<?= e($code) ?>" rows="2"
                                          lang="<?= e($code) ?>"></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text"><?= e(t('translation.fallback_help')) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="module-sort"><?= e(t('field.sort_order')) ?></label>
                        <input type="number" class="form-control" id="module-sort" name="sort_order" data-type="int" min="0" value="0">
                    </div>
                    <div class="col-sm-8 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="module-active" name="is_active" checked>
                            <label class="form-check-label" for="module-active"><?= e(t('module.active_help')) ?></label>
                        </div>
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
<?php require __DIR__ . '/_layout/footer.php'; ?>
