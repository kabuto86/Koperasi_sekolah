<?php
// Template 3 — Klasik — layout store ibu bapa (W5). PAPARAN SAHAJA: tiada logik harga/stok di sini.
// Struktur & id sama seperti Template 1 (logik halaman dalam assets/js/store/{halaman}.js, dikongsi semua template);
// rupa dari css/theme.css di atas gaya asas Template 1. Pemboleh ubah dari store/index.php: $schoolSlug, $storePage, $templateCode
$storeNav = require APP_ROOT . '/templates/template1/partials/nav-items.php';
$templateCss = function ($path) {
    return View::url('templates/' . $path) . '?v=' . Config::get('asset_version');
};

// Halaman yang mencetak slip / resit & memaparkan QR
$printPages = ['order-success', 'order-detail'];
$extraCss = [$templateCss('template1/css/template.css'), $templateCss('template3/css/theme.css')];
$pageScripts = [];
if (in_array($storePage, $printPages, true)) {
    $extraCss[] = $templateCss('template1/css/print-slip.css');
    $pageScripts[] = View::asset('lib/qrcode/qrcode.js');
}
$pageScripts[] = View::asset('js/store/common.js');
$pageScripts[] = View::asset('js/store/layout.js');
$pageScripts[] = View::asset('js/store/' . $storePage . '.js');

View::partial('partials/head.php', [
    'pageTitle' => t('store.page.' . $storePage),
    'bodyClass' => 'store-body store-template3',
    'extraCss'  => $extraCss,
]);

require __DIR__ . '/partials/header.php';
?>
<main class="container store-main py-3" id="main">
    <div class="alert alert-danger d-none" id="store-error" role="alert"></div>
    <?php if ($schoolSlug !== '') {
        require APP_ROOT . '/templates/template1/partials/notices.php';
    } ?>
    <?php require __DIR__ . '/pages/' . $storePage . '.php'; ?>
</main>
<?php
require __DIR__ . '/partials/footer.php';
require APP_ROOT . '/templates/template1/partials/bottom-nav.php';

View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'pageScripts' => $pageScripts,
]);
