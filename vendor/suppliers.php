<?php
// Pembekal — dikongsi oleh semua koperasi vendor
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.suppliers_list');
$activeMenu = 'menu.suppliers';
$subNavGroup = 'suppliers';
$subNavActive = 'vendor/suppliers.php';
$pageActions = [['id' => 'btn-add-supplier', 'label' => t('supplier.add'), 'icon' => 'bi-plus-lg']];
$pageScripts = [View::asset('js/vendor/suppliers.js')];
require __DIR__ . '/_layout/header.php';
?>
<p class="small text-body-secondary"><?= e(t('supplier.shared_note')) ?></p>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="supplier-filters" role="search">
            <div class="col-12 col-md-8">
                <label class="visually-hidden" for="sup-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="sup-q" name="q" placeholder="<?= e(t('supplier.search_placeholder')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="visually-hidden" for="sup-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="sup-status" name="status">
                    <option value=""><?= e(t('common.all_statuses')) ?></option>
                    <option value="active"><?= e(t('common.active')) ?></option>
                    <option value="inactive"><?= e(t('common.inactive')) ?></option>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.name')) ?></th>
                    <th><?= e(t('field.contact')) ?></th>
                    <th class="text-center"><?= e(t('supplier.open_po')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="suppliers-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="suppliers-pagination"></div>
</div>

<div class="modal fade" id="supplier-modal" tabindex="-1" aria-labelledby="supplier-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="supplier-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="supplier-title"><?= e(t('supplier.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="sup-name"><?= e(t('supplier.name')) ?></label>
                    <input type="text" class="form-control" id="sup-name" name="name" required maxlength="120">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="sup-contact"><?= e(t('field.contact')) ?></label>
                    <input type="text" class="form-control" id="sup-contact" name="contact_name" maxlength="80">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="sup-phone"><?= e(t('field.phone')) ?></label>
                    <input type="tel" class="form-control" id="sup-phone" name="phone" required maxlength="20">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="sup-email"><?= e(t('field.email')) ?></label>
                    <input type="email" class="form-control" id="sup-email" name="email">
                    <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="sup-address"><?= e(t('field.address')) ?></label>
                    <textarea class="form-control" id="sup-address" name="address" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="sup-active" name="is_active">
                        <label class="form-check-label" for="sup-active"><?= e(t('common.active')) ?></label>
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
