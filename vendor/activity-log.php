<?php
// Log aktiviti — siapa buat apa (PELAN 5.2 modul 18, jadual activity_logs). Baca sahaja
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.activity_log');
$activeMenu = 'menu.activity_log';
$pageScripts = [View::asset('js/vendor/activity-log.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="activity-filters" role="search">
            <div class="col-12 col-lg-3">
                <label class="visually-hidden" for="act-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="act-q" name="q" placeholder="<?= e(t('activity.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="visually-hidden" for="act-user"><?= e(t('activity.user')) ?></label>
                <select class="form-select" id="act-user" name="user_id">
                    <option value=""><?= e(t('activity.all_users')) ?></option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label class="visually-hidden" for="act-scope"><?= e(t('common.cooperative')) ?></label>
                <select class="form-select" id="act-scope" name="scope">
                    <option value=""><?= e(t('activity.scope_all')) ?></option>
                    <option value="current"><?= e(t('activity.scope_current')) ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="act-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="act-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="visually-hidden" for="act-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="act-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.time')) ?></th>
                    <th><?= e(t('activity.user')) ?></th>
                    <th><?= e(t('audit.action')) ?></th>
                    <th><?= e(t('audit.detail')) ?></th>
                    <th><?= e(t('common.cooperative')) ?></th>
                </tr>
            </thead>
            <tbody id="activity-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="activity-pagination"></div>
</div>
<p class="small text-body-secondary mt-2"><?= e(t('activity.help')) ?></p>
<?php require __DIR__ . '/_layout/footer.php'; ?>
