<?php
// Bahasa & terjemahan — jadual kunci × 4 bahasa, tapis kunci hilang (PELAN 12)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.translations');
$activeMenu = 'menu.translations';
$pageScripts = [View::asset('js/owner/translations.js')];
$languages = Config::get('languages');
require __DIR__ . '/_layout/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3" id="missing-summary"></div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="translation-filters" role="search">
            <div class="col-12 col-md-5">
                <label class="visually-hidden" for="tr-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="tr-q" name="q" placeholder="<?= e(t('translation.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="tr-group"><?= e(t('translation.group')) ?></label>
                <select class="form-select" id="tr-group" name="group">
                    <option value=""><?= e(t('translation.all_groups')) ?></option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="tr-missing"><?= e(t('translation.missing_filter')) ?></label>
                <select class="form-select" id="tr-missing" name="missing">
                    <option value=""><?= e(t('translation.all_keys')) ?></option>
                    <option value="any"><?= e(t('translation.missing_any')) ?></option>
                    <?php foreach ($languages as $code): ?>
                    <option value="<?= e($code) ?>"><?= e(t('translation.missing_in', ['lang' => t('lang.' . $code)])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button type="reset" class="btn btn-outline-secondary" title="<?= e(t('btn.reset')) ?>">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span class="d-md-none"> <?= e(t('btn.reset')) ?></span>
                </button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 app-translation-table">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('translation.key')) ?></th>
                    <?php foreach ($languages as $code): ?>
                    <th lang="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></th>
                    <?php endforeach; ?>
                    <th class="text-end"><span class="visually-hidden"><?= e(t('common.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody id="translations-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="translations-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('translation.help')) ?></p>
<?php require __DIR__ . '/_layout/footer.php'; ?>
