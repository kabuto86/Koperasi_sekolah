<?php
// Template 1 — layout mobile-first. PAPARAN SAHAJA: tiada logik harga/stok di sini.
// Pemboleh ubah dari store/index.php: $schoolSlug, $storePage, $templateCode
$templateUrl = 'templates/' . $templateCode . '/';
$storeNav = require __DIR__ . '/partials/nav-items.php';

View::partial('partials/head.php', [
    'pageTitle' => t('app.tagline'), // U5: tajuk ikut halaman
    'bodyClass' => 'store-body',
    'extraCss'  => [View::url($templateUrl . 'css/template.css') . '?v=' . Config::get('asset_version')],
]);

require __DIR__ . '/partials/header.php';
?>
<main class="container store-main py-3" id="main">
    <?php require __DIR__ . '/pages/' . $storePage . '.php'; ?>
</main>
<?php
require __DIR__ . '/partials/footer.php';
require __DIR__ . '/partials/bottom-nav.php';

View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['store.js'],
    'pageScripts' => [View::url($templateUrl . 'js/layout.js') . '?v=' . Config::get('asset_version')],
]);
