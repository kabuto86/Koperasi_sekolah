<header class="navbar navbar-expand-lg bg-brand sticky-top store-topbar" data-bs-theme="dark"
        id="store-context" data-school-slug="<?= e($schoolSlug) ?>">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 min-w-0" href="<?= e(View::storeUrl($schoolSlug)) ?>">
            <i class="bi bi-shop-window" aria-hidden="true"></i>
            <span class="text-truncate" data-school-name><?= e(t('app.name')) ?></span>
        </a>

        <ul class="navbar-nav d-none d-lg-flex me-auto">
            <?php foreach ($storeNav as $navItem): ?>
            <li class="nav-item">
                <a class="nav-link<?= $navItem['page'] === $storePage ? ' active' : '' ?>"
                   href="<?= e(View::storeUrl($schoolSlug, $navItem['page'])) ?>">
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
            <a class="btn btn-sm btn-outline-light d-none d-lg-inline-block" href="<?= e(View::storeUrl($schoolSlug, 'profile')) ?>">
                <i class="bi bi-person-circle" aria-hidden="true"></i> <?= e(t('store.nav.account')) ?>
            </a>
        </div>
    </div>
</header>
