<?php
// Layout POS kaunter — bahagian bawah. Halaman boleh set $pageScripts sebelum include.
?>
</main>
<!-- Salinan resit untuk dicetak (hanya kelihatan semasa cetak — assets/css/receipt.css) -->
<div class="d-none d-print-block" id="receipt-print" aria-hidden="true"></div>
<?php
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['vendor.js', 'vendor-orders.js', 'vendor-finance.js', 'vendor-addons.js'],
    'pageScripts' => array_merge([View::asset('js/vendor-layout.js')], $pageScripts ?? []),
]);
