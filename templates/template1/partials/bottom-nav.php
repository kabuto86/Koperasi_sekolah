<?php // Navbar bawah — telefon & tablet sahaja (disembunyikan pada ≥ lg). Item modul add-on hanya di navbar atas & halaman utama ?>
<nav class="navbar fixed-bottom bg-body border-top d-lg-none d-print-none store-bottom-nav" aria-label="<?= e(t('store.nav.label')) ?>">
    <ul class="nav nav-justified w-100">
        <?php foreach ($storeNav as $navItem): ?>
        <?php if (isset($navItem['module'])) { continue; } ?>
        <li class="nav-item">
            <a class="nav-link store-bottom-link<?= $navItem['page'] === $storePage ? ' active' : '' ?>"
               href="<?= e(View::storeUrl($schoolSlug, $navItem['page'])) ?>"<?= $navItem['page'] === $storePage ? ' aria-current="page"' : '' ?>>
                <span class="position-relative d-inline-block">
                    <i class="bi <?= e($navItem['icon']) ?> fs-5" aria-hidden="true"></i>
                    <?php if ($navItem['page'] === 'cart'): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none" data-cart-count></span>
                    <?php endif; ?>
                </span>
                <span class="d-block small text-truncate"><?= e(t($navItem['key'])) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
