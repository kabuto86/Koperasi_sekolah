<?php
// Halaman log masuk statik (owner & vendor). UI sahaja — sambungan sebenar di B2.
// Pemboleh ubah: $area ('owner' | 'vendor'), $loginTitle, $panelLabel, $requires2fa (bool)
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

                    <div class="alert alert-danger d-none" id="login-error" role="alert"></div>

                    <form id="login-form" novalidate data-area="<?= e($area) ?>"
                          data-success-url="<?= e(View::url($area . '/index.php')) ?>">
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="login-remember" name="remember">
                                    <label class="form-check-label" for="login-remember"><?= e(t('auth.remember')) ?></label>
                                </div>
                                <a class="small" href="#"><?= e(t('auth.forgot')) ?></a>
                            </div>
                        </div>

                        <?php if ($requires2fa): ?>
                        <div id="login-step-otp" class="d-none">
                            <div class="mb-3">
                                <label class="form-label" for="login-otp"><?= e(t('auth.otp_label')) ?></label>
                                <input type="text" class="form-control form-control-lg text-center app-otp-input" id="login-otp"
                                       name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                                <div class="form-text"><?= e(t('auth.otp_help')) ?></div>
                                <div class="invalid-feedback"><?= e(t('auth.otp_invalid')) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-brand w-100" id="login-submit">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="login-spinner" aria-hidden="true"></span>
                            <span id="login-submit-text"><?= e(t('btn.login')) ?></span>
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-center text-body-secondary small mt-3"><?= e(t('auth.demo_note')) ?></p>
            <p class="text-center text-body-secondary small"><?= e(t('app.footer', ['year' => date('Y')])) ?></p>
        </div>
    </div>
</main>
<?php
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => [$area . '.js'],
    'pageScripts' => [View::asset('js/login.js')],
]);
