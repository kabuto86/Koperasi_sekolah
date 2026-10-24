<?php
// Log audit — tindakan sensitif seluruh platform (baca sahaja)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.audit_log');
$activeMenu = 'menu.audit_log';
$pageScripts = [View::asset('js/owner/audit-log.js')];
$actorTypes = ['owner', 'vendor_user', 'customer', 'system'];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="audit-filters" role="search">
            <div class="col-12 col-lg-4">
                <label class="visually-hidden" for="audit-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="audit-q" name="q" placeholder="<?= e(t('audit.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="audit-actor"><?= e(t('audit.actor_type')) ?></label>
                <select class="form-select" id="audit-actor" name="actor_type">
                    <option value=""><?= e(t('audit.all_actors')) ?></option>
                    <?php foreach ($actorTypes as $type): ?>
                    <option value="<?= e($type) ?>"><?= e(t('audit.actor.' . $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="audit-from"><?= e(t('field.date_from')) ?></label>
                <input type="date" class="form-control" id="audit-from" name="date_from" title="<?= e(t('field.date_from')) ?>">
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="visually-hidden" for="audit-to"><?= e(t('field.date_to')) ?></label>
                <input type="date" class="form-control" id="audit-to" name="date_to" title="<?= e(t('field.date_to')) ?>">
            </div>
            <div class="col-12 col-lg-2 d-grid">
                <button type="reset" class="btn btn-outline-secondary"><?= e(t('btn.reset')) ?></button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.time')) ?></th>
                    <th><?= e(t('audit.actor')) ?></th>
                    <th><?= e(t('audit.action')) ?></th>
                    <th><?= e(t('audit.entity')) ?></th>
                    <th><?= e(t('audit.detail')) ?></th>
                    <th><?= e(t('audit.ip')) ?></th>
                </tr>
            </thead>
            <tbody id="audit-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="audit-pagination"></div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
