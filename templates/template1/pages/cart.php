<?php // Troli: item dikumpul ikut anak (kit & ala carte boleh dicampur) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><?= e(t('store.nav.cart')) ?></h1>
    <button class="btn btn-sm btn-outline-danger d-none" type="button" id="cart-clear"><i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('store.cart.clear')) ?></button>
</div>
<div class="row g-3">
    <div class="col-12 col-lg-8" id="cart-groups"></div>
    <div class="col-12 col-lg-4 d-none" id="cart-summary-col">
        <div class="card border-0 shadow-sm store-summary">
            <div class="card-body">
                <h2 class="h6"><?= e(t('store.cart.summary')) ?></h2>
                <dl class="row small mb-2" id="cart-totals"></dl>
                <div class="alert alert-warning small py-2 d-none" id="cart-problem"><?= e(t('store.checkout.fix_cart')) ?></div>
                <a class="btn btn-brand btn-lg w-100" href="<?= e(View::storeUrl($schoolSlug, 'checkout')) ?>" id="cart-checkout"><?= e(t('store.cart.checkout')) ?></a>
                <div class="d-flex gap-2 mt-2">
                    <a class="btn btn-sm btn-outline-brand flex-fill" href="<?= e(View::storeUrl($schoolSlug, 'kit')) ?>"><i class="bi bi-backpack" aria-hidden="true"></i> <?= e(t('store.nav.kit')) ?></a>
                    <a class="btn btn-sm btn-outline-brand flex-fill" href="<?= e(View::storeUrl($schoolSlug, 'catalog')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('store.cart.add_more')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
