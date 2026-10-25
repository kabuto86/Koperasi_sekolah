<?php // Checkout: semak item ikut anak → siapa ambil → kaedah bayaran (ikut tetapan koperasi) ?>
<a class="btn btn-sm btn-link px-0 mb-2" href="<?= e(View::storeUrl($schoolSlug, 'cart')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e(t('store.nav.cart')) ?></a>
<h1 class="h5 mb-3"><?= e(t('store.page.checkout')) ?></h1>
<div class="alert alert-warning d-none" id="checkout-verify" role="alert">
    <i class="bi bi-envelope-exclamation" aria-hidden="true"></i> <?= e(t('store.checkout.verify_first')) ?>
    <a class="alert-link" href="<?= e(View::storeUrl($schoolSlug, 'profile')) ?>"><?= e(t('store.verify.open')) ?></a>
</div>
<div class="alert alert-danger d-none" id="checkout-blocked" role="alert">
    <i class="bi bi-door-closed" aria-hidden="true"></i> <span id="checkout-blocked-text"></span>
</div>
<form class="row g-3" id="checkout-form" novalidate>
    <div class="col-12 col-lg-7">
        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3"><span class="badge rounded-pill text-bg-brand me-1">1</span> <?= e(t('store.checkout.review')) ?></h2>
                <div id="checkout-groups"></div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-1"><span class="badge rounded-pill text-bg-brand me-1">2</span> <?= e(t('handover.who_collects')) ?></h2>
                <p class="small text-body-secondary"><?= e(t('store.checkout.pickup_help')) ?></p>
                <div class="d-grid gap-2" role="radiogroup" aria-label="<?= e(t('handover.who_collects')) ?>">
                    <?php foreach (['student' => 'bi-person-badge', 'parent' => 'bi-person', 'proxy' => 'bi-person-check'] as $type => $icon): ?>
                    <input class="btn-check" type="radio" name="pickup_by_type" id="pickup-<?= e($type) ?>" value="<?= e($type) ?>" required<?= $type === 'student' ? ' checked' : '' ?>>
                    <label class="btn btn-outline-brand text-start store-choice" for="pickup-<?= e($type) ?>">
                        <i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i>
                        <span class="fw-semibold"><?= e(t('store.checkout.pickup.' . $type)) ?></span>
                        <span class="d-block small ms-4" data-pickup-hint="<?= e($type) ?>"><?= e(t('store.checkout.pickup_hint.' . $type)) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 d-none" id="checkout-proxy">
                    <label class="form-label" for="checkout-proxy-name"><?= e(t('store.checkout.proxy_name')) ?></label>
                    <input class="form-control" type="text" id="checkout-proxy-name" name="pickup_by_name" maxlength="100" placeholder="<?= e(t('store.checkout.proxy_placeholder')) ?>">
                    <div class="invalid-feedback"><?= e(t('error.required')) ?></div>
                </div>
                <fieldset class="mt-3 d-none" id="checkout-slot">
                    <legend class="form-label fs-6 fw-semibold mb-1"><i class="bi bi-clock" aria-hidden="true"></i> <?= e(t('store.slot.title')) ?></legend>
                    <p class="small text-body-secondary mb-2"><?= e(t('store.slot.help')) ?></p>
                    <div class="d-grid gap-2" id="checkout-slot-list"></div>
                    <div class="invalid-feedback d-block d-none" id="checkout-slot-error"><?= e(t('store.slot.choose')) ?></div>
                </fieldset>
                <div class="mt-3 d-none" id="checkout-code">
                    <label class="form-label" for="checkout-code-input"><?= e(t('store.checkout.code_label')) ?></label>
                    <input class="form-control store-code-input" type="text" id="checkout-code-input" name="pickup_code" inputmode="numeric"
                           pattern="[0-9]{4}" maxlength="4" autocomplete="off">
                    <div class="form-text" id="checkout-code-help"></div>
                    <div class="invalid-feedback"><?= e(t('handover.code_format')) ?></div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3"><span class="badge rounded-pill text-bg-brand me-1">3</span> <?= e(t('field.payment_method')) ?></h2>
                <div class="d-grid gap-2" id="checkout-methods" role="radiogroup" aria-label="<?= e(t('field.payment_method')) ?>">
                    <input class="btn-check" type="radio" name="payment_method" id="method-online" value="online" required>
                    <label class="btn btn-outline-brand text-start store-choice d-none" for="method-online">
                        <i class="bi bi-credit-card me-2" aria-hidden="true"></i><span class="fw-semibold"><?= e(t('store.checkout.method.online')) ?></span>
                        <span class="d-block small ms-4" id="method-online-hint"></span>
                    </label>
                    <input class="btn-check" type="radio" name="payment_method" id="method-cash" value="cash" required>
                    <label class="btn btn-outline-brand text-start store-choice d-none" for="method-cash">
                        <i class="bi bi-cash-coin me-2" aria-hidden="true"></i><span class="fw-semibold"><?= e(t('store.checkout.method.cash')) ?></span>
                        <span class="d-block small ms-4" id="method-cash-hint"></span>
                    </label>
                </div>
                <div class="invalid-feedback d-block d-none" id="checkout-method-error"><?= e(t('store.checkout.choose_method')) ?></div>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card border-0 shadow-sm store-summary">
            <div class="card-body">
                <h2 class="h6"><?= e(t('store.cart.summary')) ?></h2>
                <label class="form-label small" for="checkout-coupon"><?= e(t('store.checkout.coupon')) ?></label>
                <div class="input-group mb-3">
                    <input class="form-control text-uppercase" type="text" id="checkout-coupon" name="coupon_code" maxlength="20" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="checkout-coupon-apply"><?= e(t('store.checkout.apply')) ?></button>
                    <div class="invalid-feedback" id="checkout-coupon-error"></div>
                </div>
                <dl class="row small mb-2" id="checkout-totals"></dl>
                <button class="btn btn-brand btn-lg w-100" type="submit" id="checkout-submit"><?= e(t('store.checkout.place')) ?></button>
                <p class="small text-body-secondary mt-2 mb-0"><?= e(t('store.checkout.terms')) ?></p>
            </div>
        </section>
    </div>
</form>

<?php require __DIR__ . '/../partials/gateway-modal.php'; ?>
