<?php
// Halaman log masuk owner & vendor (B2: sesi sebenar). Langkah 1 e-mel + kata laluan; langkah 2 kod 2FA
// (owner sentiasa; vendor jika disediakan). Owner tanpa 2FA → sediakan aplikasi pengesah (QR) di langkah 2,
// kemudian kod pemulihan dipapar sekali sebelum masuk panel.
// Pemboleh ubah: $area ('owner' | 'vendor'), $loginTitle, $panelLabel, $notice (amaran, boleh kosong)
View::partial('partials/head.php', ['pageTitle' => $loginTitle, 'bodyClass' => 'app-login bg-body-tertiary']);
?>
<div class="d-flex justify-content-end p-3">
    <?php View::partial('partials/lang-switcher.php', ['switcherClass' => 'btn-outline-dark']); ?>
</div>
<main class="container app-login-main" id="main">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <div class="app-login-logo mx-auto mb-2"><i class="bi bi-shop-window" aria-hidden="true"></i></div>
                <div class="fw-bold fs-4 text-brand"><?= e(t('app.name')) ?></div>
                <div class="text-body-secondary small"><?= e($panelLabel) ?></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h5 mb-3"><?= e($loginTitle) ?></h1>

                    <?php if ($notice !== ''): ?>
                    <div class="alert alert-warning" role="alert"><?= e($notice) ?></div>
                    <?php endif; ?>
                    <div class="alert alert-danger d-none" id="login-error" role="alert"></div>

                    <form id="login-form" novalidate data-area="<?= e($area) ?>"
                          data-success-url="<?= e(View::url($area . '/login.php')) ?>">
                        <div id="login-step-password">
                            <div class="mb-3">
                                <label class="form-label" for="login-email"><?= e(t('auth.email')) ?></label>
                                <input type="email" class="form-control" id="login-email" name="email" autocomplete="username" required>
                                <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="login-password"><?= e(t('auth.password')) ?></label>
                                <input type="password" class="form-control" id="login-password" name="password" autocomplete="current-password" required>
                                <div class="invalid-feedback"><?= e(t('auth.password_required')) ?></div>
                            </div>
                            <div class="text-end mb-3">
                                <a class="small" href="<?= e(View::url($area . '/forgot-password.php')) ?>"><?= e(t('auth.forgot')) ?></a>
                            </div>
                        </div>

                        <div id="login-step-otp" class="d-none">
                            <div class="d-none" id="login-setup">
                                <p class="small mb-2"><?= e(t('auth.setup_2fa_text')) ?></p>
                                <ol class="small ps-3 mb-2">
                                    <li><?= e(t('twofa.step_app')) ?></li>
                                    <li><?= e(t('twofa.step_scan')) ?></li>
                                    <li><?= e(t('twofa.step_code')) ?></li>
                                </ol>
                                <div class="text-center mb-2">
                                    <img class="app-qr border rounded p-2 bg-white" id="login-setup-qr" src="data:," alt="<?= e(t('twofa.qr_alt')) ?>">
                                </div>
                                <label class="form-label small" for="login-setup-secret"><?= e(t('twofa.manual_key')) ?></label>
                                <input class="form-control form-control-sm font-monospace text-center mb-3" id="login-setup-secret" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="login-otp"><?= e(t('auth.otp_label')) ?></label>
                                <input type="text" class="form-control form-control-lg text-center app-otp-input" id="login-otp"
                                       name="code" pattern="[0-9]{6}|[A-Za-z0-9]{4}-?[A-Za-z0-9]{4}" maxlength="9" autocomplete="one-time-code">
                                <div class="form-text" id="login-otp-help"><?= e(t('auth.otp_help_recovery')) ?></div>
                                <div class="invalid-feedback"><?= e(t('auth.otp_invalid')) ?></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-brand w-100" id="login-submit">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="login-spinner" aria-hidden="true"></span>
                            <span id="login-submit-text"><?= e(t('btn.login')) ?></span>
                        </button>
                    </form>

                    <div class="d-none" id="login-recovery">
                        <div class="alert alert-success small"><i class="bi bi-shield-check" aria-hidden="true"></i> <?= e(t('twofa.enabled_toast')) ?></div>
                        <h2 class="h6"><?= e(t('twofa.recovery_codes')) ?></h2>
                        <p class="small text-body-secondary"><?= e(t('twofa.recovery_help')) ?></p>
                        <div class="row row-cols-2 g-2 text-center font-monospace mb-3" id="login-recovery-list"></div>
                        <button class="btn btn-brand w-100" type="button" id="login-continue"><?= e(t('auth.continue')) ?></button>
                    </div>
                </div>
            </div>
            <p class="text-center text-body-secondary small"><?= e(t('app.footer', ['year' => date('Y')])) ?></p>
        </div>
    </div>
</main>
<?php
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'pageScripts' => [View::asset('lib/qrcode/qrcode.js'), View::asset('js/login.js')],
]);
