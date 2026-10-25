<?php
// Modul add-on: advanced_report — halaman sementara (modul belum dibina dalam Peringkat 1)
require __DIR__ . '/../../core/bootstrap.php';

$moduleCode = 'advanced_report';
$pageTitle = t('module.advanced_report');
$activeMenu = 'module.advanced_report';
$pageScripts = [View::asset('js/vendor/addons/coming-soon.js')];
require __DIR__ . '/../_layout/header.php';
require __DIR__ . '/../_layout/addons/coming-soon.php';
require __DIR__ . '/../_layout/footer.php';
