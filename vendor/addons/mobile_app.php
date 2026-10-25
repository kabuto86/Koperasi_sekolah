<?php
// Modul add-on: mobile_app — halaman sementara (modul belum dibina dalam Peringkat 1)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'mobile_app';
$pageTitle = t('module.mobile_app');
$activeMenu = 'module.mobile_app';
$pageScripts = [View::asset('js/vendor/addons/coming-soon.js')];
require __DIR__ . '/../_layout/header.php';
require __DIR__ . '/../_layout/addons/coming-soon.php';
require __DIR__ . '/../_layout/footer.php';
