<?php
// Lupa / tetapkan kata laluan panel owner & vendor (B2). Pautan reset & jemputan kakitangan dari e-mel → ?token=
// Pemboleh ubah: $area ('owner' | 'vendor'), $panelLabel, $mode ('forgot' | 'reset')
$token = preg_match('/^[a-f0-9]{64}$/', $_GET['token'] ?? '') ? $_GET['token'] : '';
$pageTitle = $mode === 'forgot' ? t('auth.forgot_title') : t('auth.reset_title');
View::partial('partials/head.php', ['pageTitle' => $pageTitle, 'bodyClass' => 'app-login bg-body-tertiary']);
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
                    <h1 class="h5 mb-3"><?= e($pageTitle) ?></h1>

                    <?php if ($mode === 'forgot'): ?>
                    <p class="small text-body-secondary"><?= e(t('store.forgot.text')) ?></p>
                    <div class="alert alert-success small d-none" id="forgot-sent" role="status"><?= e(t('store.forgot.sent')) ?></div>
                    <form id="forgot-form" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="forgot-email"><?= e(t('auth.email')) ?></label>
                            <input type="email" class="form-control" id="forgot-email" name="email" autocomplete="username" required>
                            <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                        </div>
                        <button type="submit" class="btn btn-brand w-100"><?= e(t('store.forgot.send')) ?></button>
                    </form>
                    <?php elseif ($token === ''): ?>
                    <div class="alert alert-danger" role="alert"><?= e(t('store.auth.token_invalid')) ?></div>
                    <?php else: ?>
                    <form id="reset-form" novalidate>
                        <input type="hidden" name="token" value="<?= e($token) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="reset-password"><?= e(t('profile.new_password')) ?></label>
                            <input type="password" class="form-control" id="reset-password" name="password" autocomplete="new-password" minlength="10" required>
                            <div class="invalid-feedback"><?= e(t('profile.password_too_short')) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reset-confirm"><?= e(t('profile.confirm_password')) ?></label>
                            <input type="password" class="form-control" id="reset-confirm" name="password_confirm" autocomplete="new-password" required>
                            <div class="invalid-feedback"><?= e(t('profile.password_mismatch')) ?></div>
                        </div>
                        <button type="submit" class="btn btn-brand w-100"><?= e(t('btn.save')) ?></button>
                    </form>
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a class="small" id="password-login-link" href="<?= e(View::url($area . '/login.php')) ?>"><?= e(t('store.forgot.back_login')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'pageScripts' => [View::asset('js/password-page.js')],
]);
