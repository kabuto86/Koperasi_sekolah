<?php
// Titik masuk store ibu bapa: slug → koperasi → template → halaman.
// Peringkat 1: tiada DB; nama sekolah dimuat oleh JS melalui Api.get('store/school').
require __DIR__ . '/../core/bootstrap.php';

$schoolSlug = $_GET['school'] ?? 'sk-taman-melati';
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

require $templateDir . '/layout.php';
