<?php
// Ibu bapa & murid koperasi semasa — baca sahaja + nota dalaman vendor
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.customers');
$activeMenu = 'menu.customers';
$pageScripts = [View::asset('js/vendor/order-common.js'), View::asset('js/vendor/customers.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e(t('customer.privacy_note')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="customer-filters" role="search">
            <div class="col-12 col-md-6">
                <label class="visually-hidden" for="cus-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="cus-q" name="q" placeholder="<?= e(t('customer.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="cus-grade"><?= e(t('field.grade')) ?></label>
                <select class="form-select" id="cus-grade" name="grade"><option value=""><?= e(t('common.all_grades')) ?></option></select>
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="cus-class"><?= e(t('field.class')) ?></label>
                <select class="form-select" id="cus-class" name="class_name"><option value=""><?= e(t('order.all_classes')) ?></option></select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('order.parent')) ?></th>
                    <th><?= e(t('order.children')) ?></th>
                    <th class="text-center"><?= e(t('customer.orders')) ?></th>
                    <th class="text-end"><?= e(t('customer.total_spent')) ?></th>
                    <th><?= e(t('customer.last_order')) ?></th>
                    <th><?= e(t('field.note')) ?></th>
                </tr>
            </thead>
            <tbody id="customers-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="customers-pagination"></div>
</div>

<div class="modal fade" id="note-modal" tabindex="-1" aria-labelledby="note-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="note-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="note-title"><?= e(t('customer.note_title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="customer_id">
                <p class="fw-semibold mb-2" id="note-customer"></p>
                <label class="form-label" for="note-text"><?= e(t('field.note')) ?></label>
                <textarea class="form-control" id="note-text" name="note" rows="3" maxlength="500"></textarea>
                <div class="form-text"><?= e(t('customer.note_help')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
