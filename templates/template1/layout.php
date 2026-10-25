<?php
// Template 1 — layout mobile-first. PAPARAN SAHAJA: tiada logik harga/stok di sini.
// Logik setiap halaman dalam assets/js/store/{halaman}.js (sama untuk semua template).
// Pemboleh ubah dari store/index.php: $schoolSlug, $storePage, $templateCode
$templateUrl = 'templates/' . $templateCode . '/';
$storeNav = require __DIR__ . '/partials/nav-items.php';
$templateCss = function ($file) use ($templateUrl) {
    return View::url($templateUrl . 'css/' . $file) . '?v=' . Config::get('asset_version');
};

// Halaman yang mencetak slip / resit & memaparkan QR
$printPages = ['order-success', 'order-detail'];
$extraCss = [$templateCss('template.css')];
$pageScripts = [];
if (in_array($storePage, $printPages, true)) {
    $extraCss[] = $templateCss('print-slip.css');
    $pageScripts[] = View::asset('lib/qrcode/qrcode.js');
}
// Peringkat 1: mock store mengira jumlah dengan fungsi troli yang sama seperti POS (posTotals)
if (Config::get('use_mock')) {
    $pageScripts[] = View::asset('js/vendor/pos-cart.js');
}
$pageScripts[] = View::asset('js/store/common.js');
$pageScripts[] = View::asset('js/store/layout.js');
$pageScripts[] = View::asset('js/store/' . $storePage . '.js');

View::partial('partials/head.php', [
    'pageTitle' => t('store.page.' . $storePage),
    'bodyClass' => 'store-body',
    'extraCss'  => $extraCss,
]);

require __DIR__ . '/partials/header.php';
?>
<main class="container store-main py-3" id="main">
    <div class="alert alert-danger d-none" id="store-error" role="alert"></div>
    <?php if ($schoolSlug !== '') {
        require __DIR__ . '/partials/notices.php';
    } ?>
    <?php require __DIR__ . '/pages/' . $storePage . '.php'; ?>
</main>
<?php
require __DIR__ . '/partials/footer.php';
require __DIR__ . '/partials/bottom-nav.php';

View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['vendor.js', 'vendor-orders.js', 'vendor-finance.js', 'vendor-addons.js', 'store.js'],
    'pageScripts' => $pageScripts,
]);
