<?php // Butiran produk: varian, kuantiti, pilih anak → troli ?>
<a class="btn btn-sm btn-link px-0 mb-2" href="<?= e(View::storeUrl($schoolSlug, 'catalog')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e(t('store.nav.catalog')) ?></a>
<div class="row g-3" id="product-view">
    <div class="col-12 col-md-5">
        <div class="card border-0 shadow-sm"><div class="card-body p-2" id="product-image"></div></div>
    </div>
    <div class="col-12 col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-body-secondary" id="product-category"></div>
                <h1 class="h4 mb-1" id="product-name"><?= e(t('common.loading')) ?></h1>
                <div class="fs-4 mb-2" id="product-price"></div>
                <div class="mb-2" id="product-badges"></div>
                <p class="text-body-secondary" id="product-description"></p>

                <form id="product-form" novalidate>
                    <div class="mb-3 d-none" id="product-variant-box">
                        <span class="form-label d-block" id="product-variant-label"><?= e(t('store.product.choose_variant')) ?></span>
                        <div class="d-flex flex-wrap gap-2" id="product-variants" role="radiogroup" aria-labelledby="product-variant-label"></div>
                        <div class="invalid-feedback d-block d-none" id="product-variant-error"><?= e(t('store.product.variant_required')) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="form-label d-block"><?= e(t('field.qty')) ?></span>
                        <div id="product-qty"></div>
                        <div class="form-text" id="product-limit"></div>
                    </div>
                    <div class="mb-3">
                        <span class="form-label d-block" id="product-child-label"><?= e(t('store.product.for_child')) ?></span>
                        <div class="d-grid gap-2 store-child-options" id="product-children" role="radiogroup" aria-labelledby="product-child-label"></div>
                        <div class="invalid-feedback d-block d-none" id="product-child-error"><?= e(t('store.cart.choose_child')) ?></div>
                    </div>
                    <button class="btn btn-brand btn-lg w-100" type="submit" id="product-add"><i class="bi bi-cart-plus" aria-hidden="true"></i> <?= e(t('store.product.add_to_cart')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
