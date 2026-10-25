<?php // Profil ibu bapa: maklumat & bahasa, kata laluan, pengesahan e-mel, padam akaun (PDPA) ?>
<h1 class="h5 mb-3"><?= e(t('store.page.profile')) ?></h1>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 d-none" id="profile-verify" role="alert">
    <i class="bi bi-envelope-exclamation" aria-hidden="true"></i>
    <span class="flex-grow-1"><?= e(t('store.verify.banner')) ?></span>
    <button class="btn btn-sm btn-warning" type="button" id="profile-resend"><?= e(t('store.verify.resend')) ?></button>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= e(t('store.profile.details')) ?></h2>
                <form class="row g-3" id="profile-form" novalidate>
                    <div class="col-12">
                        <label class="form-label" for="profile-name"><?= e(t('field.full_name')) ?></label>
                        <input class="form-control" type="text" id="profile-name" name="full_name" maxlength="100" autocomplete="name" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="profile-email"><?= e(t('auth.email')) ?></label>
                        <input class="form-control" type="email" id="profile-email" name="email" readonly disabled>
                        <div class="form-text"><?= e(t('store.profile.email_help')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="profile-phone"><?= e(t('field.phone')) ?></label>
                        <input class="form-control" type="tel" id="profile-phone" name="phone" inputmode="tel" autocomplete="tel"
                               pattern="^(\+?60|0)1[0-9][\s-]?[0-9]{7,8}$" required>
                        <div class="invalid-feedback"><?= e(t('store.auth.phone_invalid')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="profile-relationship"><?= e(t('store.auth.relationship')) ?></label>
                        <select class="form-select" id="profile-relationship" name="relationship" required>
                            <?php foreach (['mother', 'father', 'guardian'] as $relation): ?>
                            <option value="<?= e($relation) ?>"><?= e(t('customer.relationship.' . $relation)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="profile-language"><?= e(t('store.auth.language')) ?></label>
                        <select class="form-select" id="profile-language" name="language" required>
                            <?php foreach (Config::get('languages') as $code): ?>
                            <option value="<?= e($code) ?>"><?= e(t('lang.' . $code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= e(t('store.profile.language_help')) ?></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-brand" type="submit"><?= e(t('btn.save')) ?></button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= e(t('store.profile.change_password')) ?></h2>
                <form class="row g-3" id="password-form" novalidate>
                    <div class="col-12">
                        <label class="form-label" for="pw-current"><?= e(t('store.profile.current_password')) ?></label>
                        <input class="form-control" type="password" id="pw-current" name="current_password" autocomplete="current-password" required>
                        <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="pw-new"><?= e(t('store.profile.new_password')) ?></label>
                        <input class="form-control" type="password" id="pw-new" name="password" autocomplete="new-password" minlength="8" required>
                        <div class="invalid-feedback"><?= e(t('store.auth.password_min')) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="pw-confirm"><?= e(t('store.auth.password_confirm')) ?></label>
                        <input class="form-control" type="password" id="pw-confirm" name="password_confirm" autocomplete="new-password" required>
                        <div class="invalid-feedback"><?= e(t('store.auth.password_mismatch')) ?></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-brand" type="submit"><?= e(t('store.profile.change_password')) ?></button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="list-group shadow-sm mb-3">
            <a class="list-group-item list-group-item-action" href="<?= e(View::storeUrl($schoolSlug, 'children')) ?>"><i class="bi bi-people me-2" aria-hidden="true"></i><?= e(t('store.nav.children')) ?></a>
            <?php if ($schoolSlug !== ''): ?>
            <a class="list-group-item list-group-item-action" href="<?= e(View::storeUrl($schoolSlug, 'orders')) ?>"><i class="bi bi-receipt me-2" aria-hidden="true"></i><?= e(t('store.nav.orders')) ?></a>
            <a class="list-group-item list-group-item-action d-none" data-module="news" href="<?= e(View::storeUrl($schoolSlug, 'news')) ?>"><i class="bi bi-megaphone me-2" aria-hidden="true"></i><?= e(t('store.nav.news')) ?></a>
            <a class="list-group-item list-group-item-action d-none" data-module="clearance" href="<?= e(View::storeUrl($schoolSlug, 'clearance')) ?>"><i class="bi bi-tags me-2" aria-hidden="true"></i><?= e(t('store.nav.clearance')) ?></a>
            <?php endif; ?>
            <a class="list-group-item list-group-item-action" href="<?= e(View::storeUrl('', 'school-select')) ?>"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i><?= e(t('store.change_school')) ?></a>
            <button class="list-group-item list-group-item-action text-danger" type="button" data-logout><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i><?= e(t('btn.logout')) ?></button>
        </section>

        <section class="card border-danger-subtle shadow-sm">
            <div class="card-body">
                <h2 class="h6 text-danger"><?= e(t('store.profile.delete_title')) ?></h2>
                <p class="small text-body-secondary"><?= e(t('store.profile.delete_text')) ?></p>
                <form id="delete-form" novalidate>
                    <label class="form-label small" for="delete-password"><?= e(t('store.profile.delete_password')) ?></label>
                    <input class="form-control mb-2" type="password" id="delete-password" name="password" autocomplete="current-password" required>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="delete-confirm" name="confirm" required>
                        <label class="form-check-label small" for="delete-confirm"><?= e(t('store.profile.delete_confirm')) ?></label>
                    </div>
                    <button class="btn btn-outline-danger" type="submit"><?= e(t('store.profile.delete_button')) ?></button>
                </form>
            </div>
        </section>
    </div>
</div>
