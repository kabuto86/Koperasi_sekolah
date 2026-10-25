<?php // Daftar akaun ibu bapa. Tiada no. IC (PDPA) ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-9 col-lg-7">
        <div class="card border-0 shadow-sm" id="register-card">
            <div class="card-body p-4">
                <h1 class="h5 mb-1"><?= e(t('store.auth.register_title')) ?></h1>
                <p class="small text-body-secondary mb-3"><?= e(t('store.auth.register_text')) ?></p>
                <form id="register-form" class="row g-3" novalidate>
                    <div class="col-12">
                        <label class="form-label" for="reg-name"><?= e(t('field.full_name')) ?></label>
                        <input class="form-control" type="text" id="reg-name" name="full_name" autocomplete="name" maxlength="100" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="reg-phone"><?= e(t('field.phone')) ?></label>
                        <input class="form-control" type="tel" id="reg-phone" name="phone" autocomplete="tel" inputmode="tel"
                               placeholder="012-3456789" pattern="^(\+?60|0)1[0-9][\s-]?[0-9]{7,8}$" required>
                        <div class="invalid-feedback"><?= e(t('store.auth.phone_invalid')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="reg-email"><?= e(t('auth.email')) ?></label>
                        <input class="form-control" type="email" id="reg-email" name="email" autocomplete="email" required>
                        <div class="invalid-feedback"><?= e(t('auth.email_invalid')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="reg-password"><?= e(t('auth.password')) ?></label>
                        <input class="form-control" type="password" id="reg-password" name="password" autocomplete="new-password" minlength="8" required>
                        <div class="form-text"><?= e(t('store.auth.password_min')) ?></div>
                        <div class="invalid-feedback"><?= e(t('store.auth.password_min')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="reg-password2"><?= e(t('store.auth.password_confirm')) ?></label>
                        <input class="form-control" type="password" id="reg-password2" name="password_confirm" autocomplete="new-password" required>
                        <div class="invalid-feedback"><?= e(t('store.auth.password_mismatch')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="form-label d-block" id="reg-rel-label"><?= e(t('store.auth.relationship')) ?></span>
                        <div class="btn-group w-100" role="group" aria-labelledby="reg-rel-label">
                            <?php foreach (['mother', 'father', 'guardian'] as $index => $relation): ?>
                            <input class="btn-check" type="radio" name="relationship" id="reg-rel-<?= e($relation) ?>" value="<?= e($relation) ?>" required<?= $index === 0 ? ' checked' : '' ?>>
                            <label class="btn btn-outline-brand" for="reg-rel-<?= e($relation) ?>"><?= e(t('customer.relationship.' . $relation)) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="reg-language"><?= e(t('store.auth.language')) ?></label>
                        <select class="form-select" id="reg-language" name="language" required>
                            <?php foreach (Config::get('languages') as $code): ?>
                            <option value="<?= e($code) ?>"<?= $code === Lang::code() ? ' selected' : '' ?>><?= e(t('lang.' . $code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check p-3 ps-5 border rounded-3 bg-body-tertiary">
                            <input class="form-check-input" type="checkbox" id="reg-pdpa" name="pdpa" required>
                            <label class="form-check-label small" for="reg-pdpa"><?= e(t('store.auth.pdpa_text')) ?></label>
                            <div class="invalid-feedback"><?= e(t('store.auth.pdpa_required')) ?></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-brand w-100 btn-lg" type="submit"><?= e(t('store.auth.register_submit')) ?></button>
                    </div>
                </form>
                <hr>
                <p class="text-center small mb-0">
                    <?= e(t('store.auth.have_account')) ?> <a href="<?= e(View::storeUrl($schoolSlug, 'login')) ?>"><?= e(t('btn.login')) ?></a>
                </p>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none" id="register-done">
            <div class="card-body p-4 text-center">
                <i class="bi bi-envelope-check display-5 text-brand" aria-hidden="true"></i>
                <h2 class="h5 mt-2"><?= e(t('store.verify.sent_title')) ?></h2>
                <p class="text-body-secondary" id="register-done-text"></p>
                <button class="btn btn-outline-secondary btn-sm mb-3" type="button" id="register-verify-demo">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i> <?= e(t('store.verify.demo_link')) ?>
                </button>
                <div class="d-grid gap-2">
                    <a class="btn btn-brand" href="<?= e(View::storeUrl($schoolSlug, 'children', ['add' => 1])) ?>"><?= e(t('store.verify.next_children')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
