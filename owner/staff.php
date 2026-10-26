<?php
// Kakitangan owner — owner_staff, status aktif, 2FA
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('menu.owner_staff');
$activeMenu = 'menu.owner_staff';
$pageActions = [['id' => 'btn-add-staff', 'label' => t('staff.add'), 'icon' => 'bi-person-plus']];
$pageScripts = [View::asset('js/permission-editor.js'), View::asset('js/owner/staff.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= e(t('field.name')) ?></th>
                    <th><?= e(t('field.role')) ?></th>
                    <th><?= e(t('twofa.short')) ?></th>
                    <th><?= e(t('field.last_login')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th class="text-end"><?= e(t('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody id="staff-body"></tbody>
        </table>
    </div>
</div>
<div class="small text-body-secondary mt-2">
    <div><strong><?= e(t('role.owner')) ?>:</strong> <?= e(t('role.owner_help')) ?></div>
    <div><strong><?= e(t('role.owner_staff')) ?>:</strong> <?= e(t('role.owner_staff_help')) ?></div>
</div>

<div class="modal fade" id="staff-modal" tabindex="-1" aria-labelledby="staff-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="staff-form" novalidate>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="staff-modal-title"><?= e(t('staff.add')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('btn.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label" for="staff-name"><?= e(t('field.full_name')) ?></label>
                    <input type="text" class="form-control" id="staff-name" name="full_name" required maxlength="100">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="staff-email"><?= e(t('field.email')) ?></label>
                    <input type="email" class="form-control" id="staff-email" name="email" required>
                    <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="staff-role"><?= e(t('field.role')) ?></label>
                    <select class="form-select" id="staff-role" name="role" required>
                        <option value="owner_staff"><?= e(t('role.owner_staff')) ?></option>
                        <option value="owner"><?= e(t('role.owner')) ?></option>
                    </select>
                </div>
                <div class="form-text" id="staff-invite-note"><?= e(t('staff.invite_note')) ?></div>
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
