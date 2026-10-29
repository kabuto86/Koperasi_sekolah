<?php
// Layout vendor — bahagian bawah. Halaman boleh set $pageScripts sebelum include.
?>
<?php if (!empty($moduleCode)): ?>
    </div><!-- #module-content -->
<?php endif; ?>
    </main>
</div>
<?php
$layoutScripts = [View::asset('js/vendor-layout.js')];
if (!empty($moduleCode)) {
    $layoutScripts[] = View::asset('js/vendor/addon-common.js');
}
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['vendor.js', 'vendor-finance.js', 'vendor-addons.js'],
    'pageScripts' => array_merge($layoutScripts, $pageScripts ?? []),
]);
