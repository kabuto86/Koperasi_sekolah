<?php
// Layout owner — bahagian atas. Halaman set $pageTitle dan $activeMenu dahulu.
View::partial('partials/head.php', ['pageTitle' => $pageTitle, 'bodyClass' => 'app-panel']);
?>
<header class="navbar bg-body border-bottom sticky-top app-topbar px-2 px-lg-3 d-print-none">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#app-sidebar" aria-controls="app-sidebar" aria-label="<?= e(t('common.toggle_menu')) ?>">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <a class="navbar-brand fw-bold text-brand me-0" href="<?= e(View::url('owner/index.php')) ?>">
            <i class="bi bi-shop-window" aria-hidden="true"></i> <?= e(t('app.name')) ?>
        </a>
        <span class="badge text-bg-dark d-none d-sm-inline"><?= e(t('app.owner_panel')) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php View::partial('partials/lang-switcher.php'); ?>
        <?php require __DIR__ . '/profile-menu.php'; ?>
    </div>
</header>

<div class="app-wrapper d-flex">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="app-main flex-grow-1 p-3 p-lg-4" id="main">
        <?php View::partial('partials/page-title.php', [
            'pageTitle'   => $pageTitle,
            'backUrl'     => $backUrl ?? null,
            'pageActions' => $pageActions ?? [],
        ]); ?>
