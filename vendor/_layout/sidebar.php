<aside class="offcanvas-lg offcanvas-start app-sidebar d-print-none" tabindex="-1" id="app-sidebar" aria-labelledby="app-sidebar-title">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title fs-6 fw-bold" id="app-sidebar-title"><?= e(t('app.vendor_panel')) ?></h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#app-sidebar"
                aria-label="<?= e(t('btn.close')) ?>"></button>
    </div>
    <div class="offcanvas-body p-0 flex-column">
        <div class="d-md-none px-3 pt-3">
            <label class="form-label small app-sidebar-label" for="coop-select-side"><?= e(t('common.cooperative')) ?></label>
            <select class="form-select form-select-sm js-coop-select" id="coop-select-side" disabled>
                <option><?= e(t('common.loading')) ?></option>
            </select>
        </div>

        <?php View::partial('partials/sidebar-menu.php', [
            'menuSections' => PanelRole::filterMenu(require __DIR__ . '/menu.php', PanelRole::permissions('vendor')),
            'activeMenu'   => $activeMenu ?? '',
        ]); ?>

        <?php if (VendorAuth::can(PanelRole::ADDON_PERMISSION)): // modul add-on hanya dengan kebenaran module.use ?>
        <div class="app-sidebar-nav w-100 pb-3">
            <div class="app-sidebar-section"><?= e(t('menu.section.addons')) ?></div>
            <ul class="nav flex-column">
                <?php foreach (require __DIR__ . '/addons.php' as $addon): ?>
                <?php
                $openHref = View::url('vendor/addons/' . $addon['code'] . '.php');
                $lockedHref = View::url('vendor/addon-store.php?module=' . $addon['code']);
                $isActive = (($activeMenu ?? '') === 'module.' . $addon['code']);
                ?>
                <li class="nav-item">
                    <a class="nav-link is-locked<?= $isActive ? ' active' : '' ?>" href="<?= e($lockedHref) ?>"
                       data-module="<?= e($addon['code']) ?>" data-open-href="<?= e($openHref) ?>"
                       data-locked-href="<?= e($lockedHref) ?>" title="<?= e(t('module.locked')) ?>">
                        <i class="bi <?= e($addon['icon']) ?>" aria-hidden="true"></i>
                        <span class="flex-grow-1"><?= e(t('module.' . $addon['code'])) ?></span>
                        <span class="badge rounded-pill text-bg-warning js-addon-lock">
                            <i class="bi bi-lock-fill" aria-hidden="true"></i> <?= e(t('module.subscribe')) ?>
                        </span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</aside>
