<?php
// Pengesahan dua faktor (TOTP) kakitangan vendor — pilihan (PELAN 11): aktifkan, kod pemulihan, pindah peranti, matikan
require __DIR__ . '/../core/bootstrap.php';

$pageTitle = t('twofa.title');
$activeMenu = 'common.profile';
$backUrl = View::url('vendor/profile.php');
$pageScripts = [View::asset('lib/qrcode/qrcode.js'), View::asset('js/vendor/two-factor.js')];
require __DIR__ . '/_layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="alert alert-info small"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= e(t('twofa.vendor_note')) ?></div>

        <div class="card border-0 shadow-sm d-none" id="twofa-enabled">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-shield-check fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <div class="fw-semibold"><?= e(t('twofa.enabled')) ?></div>
                        <div class="small text-body-secondary"><?= e(t('twofa.enabled_help')) ?></div>
                    </div>
                </div>
                <form id="regenerate-form" class="mb-3" novalidate>
                    <label class="form-label" for="regen-code"><?= e(t('twofa.regenerate_label')) ?></label>
                    <div class="input-group has-validation">
                        <input type="text" class="form-control app-otp-input" id="regen-code" name="code" inputmode="numeric"
                               pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
                        <button type="submit" class="btn btn-outline-secondary"><?= e(t('twofa.regenerate')) ?></button>
                        <div class="invalid-feedback"><?= e(t('auth.otp_invalid')) ?></div>
                    </div>
                </form>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-new-device">
                        <i class="bi bi-phone" aria-hidden="true"></i> <?= e(t('twofa.new_device')) ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-show-disable">
                        <i class="bi bi-shield-x" aria-hidden="true"></i> <?= e(t('twofa.disable')) ?>
                    </button>
                </div>

                <form id="disable-form" class="border-top pt-3 mt-3 d-none" novalidate>
                    <p class="small text-danger"><?= e(t('twofa.disable_help')) ?></p>
                    <div class="mb-2">
                        <label class="form-label" for="disable-password"><?= e(t('profile.current_password')) ?></label>
                        <input type="password" class="form-control" id="disable-password" name="password" required autocomplete="current-password">
                        <div class="invalid-feedback"><?= e(t('auth.password_required')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="disable-code"><?= e(t('twofa.disable_code')) ?></label>
                        <input type="text" class="form-control app-otp-input" id="disable-code" name="code" required maxlength="9" autocomplete="one-time-code">
                        <div class="invalid-feedback"><?= e(t('auth.otp_invalid')) ?></div>
                    </div>
                    <button type="submit" class="btn btn-danger"><?= e(t('twofa.disable')) ?></button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none" id="twofa-setup">
            <div class="card-header bg-body fw-semibold"><?= e(t('twofa.setup_title')) ?></div>
            <div class="card-body">
                <ol class="small ps-3">
                    <li><?= e(t('twofa.step_app')) ?></li>
                    <li><?= e(t('twofa.step_scan')) ?></li>
                    <li><?= e(t('twofa.step_code')) ?></li>
                </ol>
                <div class="text-center mb-3">
                    <img class="border rounded p-2 bg-white app-qr" id="twofa-qr" alt="<?= e(t('twofa.qr_alt')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small" for="twofa-secret"><?= e(t('twofa.manual_key')) ?></label>
                    <input type="text" class="form-control font-monospace text-center" id="twofa-secret" readonly>
                </div>
                <form id="enable-form" novalidate>
                    <label class="form-label" for="enable-code"><?= e(t('auth.otp_label')) ?></label>
                    <input type="text" class="form-control form-control-lg text-center app-otp-input mb-1" id="enable-code" name="code"
                           inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
                    <div class="invalid-feedback mb-2"><?= e(t('auth.otp_invalid')) ?></div>
                    <button type="submit" class="btn btn-brand w-100 mt-2"><?= e(t('twofa.enable')) ?></button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3 d-none" id="recovery-card">
            <div class="card-header bg-body fw-semibold"><?= e(t('twofa.recovery_codes')) ?></div>
            <div class="card-body">
                <p class="small text-danger"><?= e(t('twofa.recovery_help')) ?></p>
                <div class="row row-cols-2 g-2 font-monospace text-center mb-3" id="recovery-list"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary d-print-none" id="btn-print-codes">
                    <i class="bi bi-printer" aria-hidden="true"></i> <?= e(t('btn.print')) ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
