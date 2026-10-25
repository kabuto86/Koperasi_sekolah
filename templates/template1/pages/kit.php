<?php // Kit sekolah: pilih anak → kit darjah dengan SEMUA item siap ditanda → nyahtanda / ubah kuantiti → troli ?>
<h1 class="h5 mb-1"><?= e(t('store.nav.kit')) ?></h1>
<p class="small text-body-secondary mb-3"><?= e(t('store.kit.intro')) ?></p>

<section class="mb-3" id="kit-child-section">
    <span class="form-label d-block fw-semibold" id="kit-child-label"><?= e(t('store.kit.choose_child')) ?></span>
    <div class="d-grid gap-2 store-child-options" id="kit-children" role="radiogroup" aria-labelledby="kit-child-label"></div>
</section>

<div id="kit-empty"></div>

<section class="card border-0 shadow-sm mb-3 d-none" id="kit-card">
    <div class="card-header bg-body border-0 pt-3">
        <h2 class="h5 mb-0" id="kit-title"></h2>
        <div class="small text-body-secondary" id="kit-child-line"></div>
    </div>
    <div class="card-body pt-2">
        <div class="alert alert-info small py-2 d-none" id="kit-note"></div>
        <div class="alert alert-secondary small py-2 d-none" id="kit-in-cart" role="status">
            <i class="bi bi-cart-check" aria-hidden="true"></i> <?= e(t('store.kit.in_cart')) ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-body-secondary"><?= e(t('store.kit.uncheck_help')) ?></span>
            <button class="btn btn-sm btn-link text-nowrap" type="button" id="kit-check-all"><?= e(t('store.kit.check_all')) ?></button>
        </div>
        <ul class="list-group list-group-flush store-kit-list" id="kit-items"></ul>
    </div>
</section>

<div class="store-sticky-bar d-none" id="kit-bar">
    <div class="container d-flex align-items-center gap-3">
        <div class="flex-grow-1 min-w-0">
            <div class="small text-body-secondary" id="kit-selected"></div>
            <div class="fs-5 fw-bold" id="kit-total" aria-live="polite"></div>
        </div>
        <button class="btn btn-brand btn-lg" type="button" id="kit-add"><i class="bi bi-cart-plus" aria-hidden="true"></i> <span id="kit-add-text"><?= e(t('store.kit.add_to_cart')) ?></span></button>
    </div>
</div>
