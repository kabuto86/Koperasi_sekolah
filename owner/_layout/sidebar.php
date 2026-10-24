<aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="app-sidebar" aria-labelledby="app-sidebar-title">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title fs-6 fw-bold" id="app-sidebar-title"><?= e(t('app.owner_panel')) ?></h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#app-sidebar"
                aria-label="<?= e(t('btn.close')) ?>"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php View::partial('partials/sidebar-menu.php', [
            'menuSections' => require __DIR__ . '/menu.php',
            'activeMenu'   => $activeMenu ?? '',
        ]); ?>
    </div>
</aside>
