<?php // Halaman utama: tanpa sekolah → pilih sekolah / sekolah terakhir; dengan sekolah → pintasan kit, katalog, pesanan ?>
<?php if ($schoolSlug === ''): ?>
<section class="p-4 mb-3 rounded-3 store-hero">
    <h1 class="h4 fw-bold mb-2"><?= e(t('store.home.choose_title')) ?></h1>
    <p class="mb-3"><?= e(t('store.home.choose_text')) ?></p>
    <form class="d-flex gap-2" action="<?= e(View::url('store/index.php')) ?>" method="get" role="search" data-native>
        <input type="hidden" name="page" value="school-select">
        <label class="visually-hidden" for="home-school-q"><?= e(t('store.school.search_label')) ?></label>
        <input class="form-control" type="search" id="home-school-q" name="q" placeholder="<?= e(t('store.school.search_placeholder')) ?>">
        <button class="btn btn-light" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden"><?= e(t('btn.search')) ?></span></button>
    </form>
</section>

<section class="mb-3 d-none" id="home-last-school">
    <h2 class="h6 text-body-secondary text-uppercase small mb-2"><?= e(t('store.home.last_school')) ?></h2>
    <div class="list-group shadow-sm" id="home-last-list"></div>
</section>

<section class="mb-3 d-none" id="home-my-schools">
    <h2 class="h6 text-body-secondary text-uppercase small mb-2"><?= e(t('store.home.my_schools')) ?></h2>
    <div class="list-group shadow-sm" id="home-my-list"></div>
</section>

<a class="btn btn-outline-brand w-100" href="<?= e(View::storeUrl('', 'school-select')) ?>">
    <i class="bi bi-search" aria-hidden="true"></i> <?= e(t('store.home.browse_schools')) ?>
</a>
<?php else: ?>
<section class="p-4 mb-3 rounded-3 store-hero">
    <h1 class="h4 fw-bold mb-1" data-school-welcome><?= e(t('common.loading')) ?></h1>
    <p class="small mb-2 store-hero-meta" id="home-school-meta"></p>
    <p class="mb-0"><?= e(t('store.welcome_text')) ?></p>
</section>

<div class="alert alert-success d-flex align-items-center gap-2 d-none" id="home-ready" role="status">
    <i class="bi bi-bag-check fs-4" aria-hidden="true"></i>
    <div class="flex-grow-1" id="home-ready-text"></div>
    <a class="btn btn-sm btn-success" href="<?= e(View::storeUrl($schoolSlug, 'orders')) ?>"><?= e(t('btn.view')) ?></a>
</div>

<div class="alert alert-warning d-flex align-items-center gap-2 d-none" id="home-verify" role="status">
    <i class="bi bi-envelope-exclamation fs-4" aria-hidden="true"></i>
    <div class="flex-grow-1"><?= e(t('store.verify.banner')) ?></div>
    <a class="btn btn-sm btn-warning" href="<?= e(View::storeUrl($schoolSlug, 'profile')) ?>"><?= e(t('store.verify.open')) ?></a>
</div>

<div class="row g-2 mb-3 store-quick">
    <div class="col-6 col-md-3">
        <a class="card h-100 border-0 shadow-sm text-decoration-none store-quick-card" href="<?= e(View::storeUrl($schoolSlug, 'kit')) ?>">
            <div class="card-body"><i class="bi bi-backpack fs-3 text-brand" aria-hidden="true"></i>
                <div class="fw-semibold mt-1"><?= e(t('store.nav.kit')) ?></div><div class="small text-body-secondary"><?= e(t('store.home.kit_text')) ?></div></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a class="card h-100 border-0 shadow-sm text-decoration-none store-quick-card" href="<?= e(View::storeUrl($schoolSlug, 'catalog')) ?>">
            <div class="card-body"><i class="bi bi-grid fs-3 text-brand" aria-hidden="true"></i>
                <div class="fw-semibold mt-1"><?= e(t('store.nav.catalog')) ?></div><div class="small text-body-secondary"><?= e(t('store.home.catalog_text')) ?></div></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a class="card h-100 border-0 shadow-sm text-decoration-none store-quick-card" href="<?= e(View::storeUrl($schoolSlug, 'orders')) ?>">
            <div class="card-body"><i class="bi bi-receipt fs-3 text-brand" aria-hidden="true"></i>
                <div class="fw-semibold mt-1"><?= e(t('store.nav.orders')) ?></div><div class="small text-body-secondary"><?= e(t('store.home.orders_text')) ?></div></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a class="card h-100 border-0 shadow-sm text-decoration-none store-quick-card" href="<?= e(View::storeUrl($schoolSlug, 'children')) ?>">
            <div class="card-body"><i class="bi bi-people fs-3 text-brand" aria-hidden="true"></i>
                <div class="fw-semibold mt-1"><?= e(t('store.nav.children')) ?></div><div class="small text-body-secondary"><?= e(t('store.home.children_text')) ?></div></div>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3 d-none" id="home-clearance" data-module="clearance">
    <div class="card-body d-flex align-items-center gap-3">
        <i class="bi bi-tags fs-2 text-danger" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold"><?= e(t('store.clearance.banner_title')) ?></div>
            <div class="small text-body-secondary"><?= e(t('store.clearance.banner_text')) ?></div>
        </div>
        <a class="btn btn-sm btn-outline-danger" href="<?= e(View::storeUrl($schoolSlug, 'clearance')) ?>"><?= e(t('btn.view')) ?></a>
    </div>
</div>

<section class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= e(t('store.home.children_title')) ?></h2>
        <div id="home-children"></div>
    </div>
</section>

<section class="card border-0 shadow-sm mb-3 d-none" id="home-news" data-module="news">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0"><?= e(t('store.nav.news')) ?></h2>
            <a class="small" href="<?= e(View::storeUrl($schoolSlug, 'news')) ?>"><?= e(t('common.view_all')) ?></a>
        </div>
        <div class="list-group list-group-flush" id="home-news-list"></div>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 mb-2"><i class="bi bi-clock me-1" aria-hidden="true"></i><?= e(t('store.home.opening_hours')) ?></h2>
        <dl class="row small mb-0" id="home-hours"></dl>
    </div>
</section>
<?php endif; ?>
