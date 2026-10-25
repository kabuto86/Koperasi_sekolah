<?php
// Layout vendor — bahagian bawah. Halaman boleh set $pageScripts sebelum include.
?>
    </main>
</div>
<?php
$layoutScripts = [View::asset('js/vendor-layout.js')];
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['vendor.js', 'vendor-orders.js', 'vendor-finance.js'],
    'pageScripts' => array_merge($layoutScripts, $pageScripts ?? []),
]);
