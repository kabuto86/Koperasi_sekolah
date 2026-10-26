<?php
// Kakitangan vendor — pentadbir, pengurus koperasi, juruwang + akses koperasi (PELAN 5.2 modul 14)
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.staff');
$activeMenu = 'menu.staff';
$pageActions = [['id' => 'btn-add-staff', 'label' => t('staff.add'), 'icon' => 'bi-person-plus']];
$pageScripts = [View::asset('js/permission-editor.js'), View::asset('js/vendor/staff.js')];
$vendorRoles = ['vendor_admin', 'coop_manager', 'cashier'];
require __DIR__ . '/_layout/header.php';
?>
<div class="alert alert-light border d-flex flex-wrap align-items-center gap-2" id="staff-limit">
    <i class="bi bi-people" aria-hidden="true"></i><span id="staff-limit-text"><?= e(t('common.loading')) ?></span>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <form class="row g-2" id="staff-filters" role="search">
            <div class="col-12 col-md-6">
                <label class="visually-hidden" for="staff-q"><?= e(t('btn.search')) ?></label>
                <input type="search" class="form-control" id="staff-q" name="q" placeholder="<?= e(t('staff_v.search_placeholder')) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="staff-role"><?= e(t('field.role')) ?></label>
                <select class="form-select" id="staff-role" name="role">
                    <option value=""><?= e(t('staff_v.all_roles')) ?></option>
                    <?php foreach ($vendorRoles as $role): ?>
                    <option value="<?= e($role) ?>"><?= e(t('role.' . $role)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="visually-hidden" for="staff-status"><?= e(t('common.status')) ?></label>
                <select class="form-select" id="staff-status" name="status">
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
                    <th><?= e(t('field.role')) ?></th>
                    <th><?= e(t('staff_v.coop_access')) ?></th>
                    <th><?= e(t('staff_v.security')) ?></th>
                    <th><?= e(t('field.last_login')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="staff-body"></tbody>
        </table>
    </div>
    <div class="card-footer bg-body" id="staff-pagination"></div>
</div>

<div class="modal fade" id="staff-modal" tabindex="-1" aria-labelledby="staff-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="staff-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="staff-title"><?= e(t('staff.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="id">
                <div class="col-12">
                    <label class="form-label" for="st-name"><?= e(t('field.full_name')) ?></label>
                    <input type="text" class="form-control" id="st-name" name="full_name" required maxlength="100">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="col-sm-7">
                    <label class="form-label" for="st-email"><?= e(t('field.email')) ?></label>
                    <input type="email" class="form-control" id="st-email" name="email" required maxlength="150">
                    <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                </div>
                <div class="col-sm-5">
                    <label class="form-label" for="st-phone"><?= e(t('field.phone')) ?></label>
                    <input type="tel" class="form-control" id="st-phone" name="phone" maxlength="20">
                </div>
                <fieldset class="col-12">
                    <legend class="form-label fs-6"><?= e(t('field.role')) ?></legend>
                    <div class="row g-2">
                        <?php foreach ($vendorRoles as $role): ?>
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="role" id="st-role-<?= e($role) ?>" value="<?= e($role) ?>" required>
                            <label class="btn btn-outline-secondary w-100 h-100 text-start" for="st-role-<?= e($role) ?>">
                                <span class="fw-semibold d-block"><?= e(t('role.' . $role)) ?></span>
                                <span class="small"><?= e(t('role.' . $role . '_help')) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="col-12" id="st-coops-box">
                    <div class="form-label"><?= e(t('staff_v.coop_access')) ?></div>
                    <div class="border rounded p-2" id="st-coops"></div>
                    <div class="form-text"><?= e(t('staff_v.coop_help')) ?></div>
                </div>
                <div class="col-sm-6 d-none" id="st-pin-box">
                    <label class="form-label" for="st-pin"><?= e(t('staff_v.pin')) ?></label>
                    <input type="password" class="form-control font-monospace" id="st-pin" name="pin" inputmode="numeric" autocomplete="new-password" pattern="[0-9]{4,6}" maxlength="6">
                    <div class="invalid-feedback"><?= e(t('staff_v.pin_format')) ?></div>
                    <div class="form-text" id="st-pin-help"><?= e(t('staff_v.pin_help')) ?></div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e(t('staff_v.invite_note')) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('btn.cancel')) ?></button>
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php View::partial('partials/permission-modal.php'); ?>
<?php require __DIR__ . '/_layout/footer.php'; ?>
