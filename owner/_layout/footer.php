<?php
// Layout owner — bahagian bawah. Halaman boleh set $pageScripts sebelum include.
?>
    </main>
</div>
<?php
View::partial('partials/ui-shell.php');
View::partial('partials/scripts.php', [
    'mockFiles'   => ['owner.js'],
    'pageScripts' => $pageScripts ?? [],
]);
