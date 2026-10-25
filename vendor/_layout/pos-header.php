<?php
// Layout POS kaunter — skrin penuh tanpa sidebar (PELAN 5.2 modul 9). Halaman set $pageTitle dahulu.
// Tab "Jualan" (halaman ini) dan "Serah barang" (skrin U3b) di bar atas.
View::partial('partials/head.php', [
    'pageTitle' => $pageTitle,
    'bodyClass' => 'app-pos',
    'extraCss'  => [View::asset('css/pos.css'), View::asset('css/receipt.css')],
]);
?>
<header class="navbar navbar-expand bg-dark app-pos-topbar px-2 gap-2 d-print-none" data-bs-theme="dark">
    <a class="btn btn-sm btn-outline-light" href="<?= e(View::url('vendor/index.php')) ?>" title="<?= e(t('pos.back_to_panel')) ?>"
       aria-label="<?= e(t('pos.back_to_panel')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </a>
    <ul class="nav nav-pills app-pos-tabs flex-nowrap">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="<?= e(View::url('vendor/pos/index.php')) ?>">
                <i class="bi bi-upc-scan" aria-hidden="true"></i> <span class="d-none d-sm-inline"><?= e(t('pos.tab_sale')) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= e(View::url('vendor/pos/handover.php')) ?>">
                <i class="bi bi-hand-index" aria-hidden="true"></i> <span class="d-none d-sm-inline"><?= e(t('menu.handover')) ?></span>
            </a>
        </li>
    </ul>
    <div class="d-none d-md-block min-w-0">
        <label class="visually-hidden" for="coop-select-pos"><?= e(t('common.select_cooperative')) ?></label>
        <select class="form-select form-select-sm js-coop-select app-coop-select" id="coop-select-pos" disabled>
            <option><?= e(t('common.loading')) ?></option>
        </select>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2 min-w-0">
        <span class="badge text-bg-light text-truncate d-none" id="pos-cashier-badge">
            <i class="bi bi-person-badge" aria-hidden="true"></i> <span id="pos-cashier-name"></span>
        </span>
        <div class="dropdown d-none" id="pos-menu">
            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-cash-stack" aria-hidden="true"></i> <span class="d-none d-lg-inline"><?= e(t('pos.shift')) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" type="button" id="btn-shift-sales"><i class="bi bi-receipt me-2" aria-hidden="true"></i><?= e(t('pos.shift_sales')) ?></button></li>
                <li><button class="dropdown-item" type="button" id="btn-lock"><i class="bi bi-lock me-2" aria-hidden="true"></i><?= e(t('pos.lock')) ?></button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item text-danger" type="button" id="btn-close-shift"><i class="bi bi-door-closed me-2" aria-hidden="true"></i><?= e(t('pos.close_shift')) ?></button></li>
            </ul>
        </div>
        <button class="btn btn-sm btn-outline-light d-none d-md-inline-block" type="button" id="btn-fullscreen"
                title="<?= e(t('pos.fullscreen')) ?>" aria-label="<?= e(t('pos.fullscreen')) ?>">
            <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
        </button>
        <?php View::partial('partials/lang-switcher.php', ['switcherClass' => 'btn-outline-light']); ?>
    </div>
</header>
<main class="app-pos-main d-print-none" id="main">
