<?php
// Modul add-on: membership — halaman sementara (modul belum dibina dalam Peringkat 1)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'membership';
$pageTitle = t('module.membership');
$activeMenu = 'module.membership';
$pageScripts = [View::asset('js/vendor/addons/coming-soon.js')];
require __DIR__ . '/../_layout/header.php';
require __DIR__ . '/../_layout/addons/coming-soon.php';
require __DIR__ . '/../_layout/footer.php';
