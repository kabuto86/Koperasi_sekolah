<?php
// Titik masuk store ibu bapa: slug → koperasi → template → halaman.
// Data sekolah, katalog & akaun dimuat oleh JS melalui Api (assets/js/store/). Akaun ibu bapa sebenar sejak B2.
require __DIR__ . '/../core/bootstrap.php';

// Sesi ibu bapa (KOPSTORE) — token CSRF dalam <body data-csrf>
CustomerAuth::start();

$schoolSlug = $_GET['school'] ?? '';
if (!preg_match('/^[a-z0-9-]{1,80}$/', $schoolSlug)) {
    $schoolSlug = '';
}

// B6: ambil dari cooperatives.template_id; template tidak dilanggan → template1
$templateCode = 'template1';

// Pratonton dari panel owner: ?template=kod (hanya jika folder wujud). B6: hadkan kepada owner
$previewTemplate = $_GET['template'] ?? '';
if (preg_match('/^[a-z0-9_]{1,40}$/', $previewTemplate) && is_file(APP_ROOT . '/templates/' . $previewTemplate . '/layout.php')) {
    $templateCode = $previewTemplate;
}
$templateDir = APP_ROOT . '/templates/' . $templateCode;
$manifest = json_decode(file_get_contents($templateDir . '/manifest.json'), true);

$storePage = $_GET['page'] ?? 'home';
$pageFile = $templateDir . '/pages/' . $storePage . '.php';
if (!in_array($storePage, $manifest['pages'], true) || !is_file($pageFile)) {
    $storePage = 'home';
}

// Halaman akaun global (ibu bapa boleh ada anak di beberapa sekolah) boleh dibuka tanpa sekolah.
// Halaman lain perlu sekolah → kembali ke halaman utama (pilih sekolah)
$pagesWithoutSchool = ['home', 'school-select', 'login', 'register', 'forgot-password', 'profile', 'children'];
if ($schoolSlug === '' && !in_array($storePage, $pagesWithoutSchool, true)) {
    header('Location: ' . View::storeUrl('', 'home'));
    exit;
}

require $templateDir . '/layout.php';
