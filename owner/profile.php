<?php
// Profil pengguna owner semasa — maklumat, kata laluan, status 2FA
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('common.profile');
$activeMenu = '';
$pageScripts = [View::asset('js/owner/profile.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <form class="card border-0 shadow-sm" id="profile-form" novalidate>
            <div class="card-header bg-body fw-semibold"><?= e(t('profile.details')) ?></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="profile-name"><?= e(t('field.full_name')) ?></label>
                    <input type="text" class="form-control" id="profile-name" name="full_name" required maxlength="100">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="profile-email"><?= e(t('field.email')) ?></label>
                    <input type="email" class="form-control" id="profile-email" name="email" required>
                    <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                </div>
                <div>
                    <label class="form-label" for="profile-language"><?= e(t('lang.label')) ?></label>
                    <select class="form-select" id="profile-language" name="language">
                        <?php foreach (Config::get('languages') as $code): ?>
                        <option value="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><?= e(t('btn.save')) ?></button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-6">
        <form class="card border-0 shadow-sm mb-3" id="password-form" novalidate>
            <div class="card-header bg-body fw-semibold"><?= e(t('profile.change_password')) ?></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="pw-current"><?= e(t('profile.current_password')) ?></label>
                    <input type="password" class="form-control" id="pw-current" name="current_password" required autocomplete="current-password">
                    <div class="invalid-feedback"><?= e(t('auth.password_required')) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="pw-new"><?= e(t('profile.new_password')) ?></label>
                    <input type="password" class="form-control" id="pw-new" name="new_password" required minlength="10" autocomplete="new-password">
                    <div class="invalid-feedback"><?= e(t('profile.password_too_short')) ?></div>
                </div>
                <div>
                    <label class="form-label" for="pw-confirm"><?= e(t('profile.confirm_password')) ?></label>
                    <input type="password" class="form-control" id="pw-confirm" name="confirm_password" required autocomplete="new-password">
                    <div class="invalid-feedback"><?= e(t('profile.password_mismatch')) ?></div>
                </div>
            </div>
            <div class="card-footer bg-body text-end">
                <button type="submit" class="btn btn-brand"><?= e(t('profile.change_password')) ?></button>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-shield-lock fs-3 text-brand" aria-hidden="true"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e(t('twofa.title')) ?></div>
                    <div class="small" id="profile-2fa-status">—</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(View::url('owner/two-factor.php')) ?>"><?= e(t('twofa.manage')) ?></a>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
