<?php // Bar atas store: nama sekolah, menu (desktop), bahasa, akaun ?>
<header class="navbar navbar-expand-lg bg-brand sticky-top store-topbar d-print-none" data-bs-theme="dark"
        id="store-context" data-school-slug="<?= e($schoolSlug) ?>" data-page="<?= e($storePage) ?>"
        data-store-url="<?= e(View::url('store/index.php')) ?>" data-pretty-url="<?= e(Config::get('pretty_store_urls') ? View::url('') : '') ?>">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 min-w-0" href="<?= e(View::storeUrl($schoolSlug)) ?>">
            <i class="bi bi-shop-window" aria-hidden="true"></i>
            <span class="text-truncate" data-school-name><?= e(t('app.name')) ?></span>
        </a>

        <ul class="navbar-nav d-none d-lg-flex me-auto">
            <?php foreach ($storeNav as $navItem): ?>
            <li class="nav-item<?= isset($navItem['module']) ? ' d-none' : '' ?>"<?= isset($navItem['module']) ? ' data-module="' . e($navItem['module']) . '"' : '' ?>>
                <a class="nav-link<?= $navItem['page'] === $storePage ? ' active' : '' ?>"
                   href="<?= e(View::storeUrl($schoolSlug, $navItem['page'])) ?>"<?= $navItem['page'] === $storePage ? ' aria-current="page"' : '' ?>>
                    <i class="bi <?= e($navItem['icon']) ?>" aria-hidden="true"></i>
                    <?= e(t($navItem['key'])) ?>
                    <?php if ($navItem['page'] === 'cart'): ?>
                    <span class="badge rounded-pill text-bg-warning d-none" data-cart-count></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php View::partial('partials/lang-switcher.php', ['switcherClass' => 'btn-outline-light']); ?>

            <a class="btn btn-sm btn-light" href="<?= e(View::storeUrl($schoolSlug, 'login')) ?>" data-auth-guest>
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                <span class="d-none d-sm-inline"><?= e(t('btn.login')) ?></span>
            </a>

            <div class="dropdown d-none" data-auth-user>
                <button class="btn btn-sm btn-outline-light dropdown-toggle store-account-btn" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="<?= e(t('store.nav.account')) ?>">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline text-truncate" data-customer-name></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= e(View::storeUrl($schoolSlug, 'profile')) ?>"><i class="bi bi-person me-2" aria-hidden="true"></i><?= e(t('store.page.profile')) ?></a></li>
                    <li><a class="dropdown-item" href="<?= e(View::storeUrl($schoolSlug, 'children')) ?>"><i class="bi bi-people me-2" aria-hidden="true"></i><?= e(t('store.nav.children')) ?></a></li>
                    <?php if ($schoolSlug !== ''): ?>
                    <li><a class="dropdown-item" href="<?= e(View::storeUrl($schoolSlug, 'orders')) ?>"><i class="bi bi-receipt me-2" aria-hidden="true"></i><?= e(t('store.nav.orders')) ?></a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= e(View::storeUrl('', 'school-select')) ?>"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i><?= e(t('store.change_school')) ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item" type="button" data-logout><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i><?= e(t('btn.logout')) ?></button></li>
                </ul>
            </div>
        </div>
    </div>
</header>
