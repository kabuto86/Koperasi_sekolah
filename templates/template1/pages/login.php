<?php // Log masuk ibu bapa: e-mel atau no. telefon + kata laluan ?>
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h5 mb-1"><?= e(t('store.auth.login_title')) ?></h1>
                <p class="small text-body-secondary mb-3"><?= e(t('store.auth.login_text')) ?></p>
                <form id="login-form" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="login-id"><?= e(t('store.auth.login_id')) ?></label>
                        <input class="form-control" type="text" id="login-id" name="login" autocomplete="username" inputmode="email" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="login-password"><?= e(t('auth.password')) ?></label>
                        <input class="form-control" type="password" id="login-password" name="password" autocomplete="current-password" required>
                        <div class="invalid-feedback"><?= e(t('auth.password_required')) ?></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="login-remember" name="remember" checked>
                            <label class="form-check-label small" for="login-remember"><?= e(t('auth.remember')) ?></label>
                        </div>
                        <a class="small" href="<?= e(View::storeUrl($schoolSlug, 'forgot-password')) ?>"><?= e(t('auth.forgot')) ?></a>
                    </div>
                    <button class="btn btn-brand w-100 btn-lg" type="submit"><?= e(t('btn.login')) ?></button>
                </form>
                <hr>
                <p class="text-center small mb-0">
                    <?= e(t('store.auth.no_account')) ?>
                    <a href="<?= e(View::storeUrl($schoolSlug, 'register')) ?>" id="login-register-link"><?= e(t('store.auth.register_title')) ?></a>
                </p>
            </div>
        </div>
        <p class="small text-body-secondary text-center mt-3"><?= e(t('store.auth.demo_login')) ?></p>
    </div>
</div>
