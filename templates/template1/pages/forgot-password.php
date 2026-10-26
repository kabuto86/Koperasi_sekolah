<?php // Lupa kata laluan: langkah 1 e-mel → pautan (token); langkah 2 (?token=) kata laluan baharu ?>
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div id="forgot-step-email">
                    <h1 class="h5 mb-1"><?= e(t('store.forgot.title')) ?></h1>
                    <p class="small text-body-secondary mb-3"><?= e(t('store.forgot.text')) ?></p>
                    <form id="forgot-form" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="forgot-email"><?= e(t('auth.email')) ?></label>
                            <input class="form-control" type="email" id="forgot-email" name="email" autocomplete="email" required>
                            <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                        </div>
                        <button class="btn btn-brand w-100" type="submit"><?= e(t('store.forgot.send')) ?></button>
                    </form>
                    <div class="alert alert-success mt-3 mb-0 d-none" id="forgot-sent" role="status">
                        <?= e(t('store.forgot.sent')) ?>
                    </div>
                </div>

                <div class="d-none" id="forgot-step-reset">
                    <h1 class="h5 mb-3"><?= e(t('store.forgot.reset_title')) ?></h1>
                    <form id="reset-form" novalidate>
                        <input type="hidden" name="token" id="reset-token">
                        <div class="mb-3">
                            <label class="form-label" for="reset-password"><?= e(t('store.profile.new_password')) ?></label>
                            <input class="form-control" type="password" id="reset-password" name="password" autocomplete="new-password" minlength="8" required>
                            <div class="invalid-feedback"><?= e(t('store.auth.password_min')) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reset-password2"><?= e(t('store.auth.password_confirm')) ?></label>
                            <input class="form-control" type="password" id="reset-password2" name="password_confirm" autocomplete="new-password" required>
                            <div class="invalid-feedback"><?= e(t('store.auth.password_mismatch')) ?></div>
                        </div>
                        <button class="btn btn-brand w-100" type="submit"><?= e(t('btn.save')) ?></button>
                    </form>
                </div>

                <hr>
                <p class="text-center small mb-0"><a href="<?= e(View::storeUrl($schoolSlug, 'login')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e(t('store.forgot.back_login')) ?></a></p>
            </div>
        </div>
    </div>
</div>
