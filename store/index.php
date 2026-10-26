<?php
// Titik masuk store ibu bapa: slug → koperasi → template → halaman (PELAN 10.5).
// URL: store/index.php?school={slug}&page=... atau /{slug}/{page} (config pretty_store_urls + .htaccess akar).
// Data sekolah, katalog, anak & troli dimuat oleh JS melalui Api (assets/js/store/).
require __DIR__ . '/../core/bootstrap.php';

$schoolSlug = $_GET['school'] ?? '';
if (!is_string($schoolSlug) || !preg_match('/^[a-z0-9-]{1,80}$/', $schoolSlug)) {
    $schoolSlug = '';
}
// Koperasi aktif diselesaikan di pelayan (StoreSchool). Slug tidak sah → halaman dipapar dengan ralat "Sekolah tidak dijumpai"
$cooperative = $schoolSlug !== '' ? StoreSchool::findBySlug($schoolSlug) : null;
if ($schoolSlug !== '' && $cooperative === null) {
    http_response_code(404);
}

// Pratonton ?template=kod: hanya owner, atau vendor pemilik koperasi ini (sesi panel masing-masing)
$previewTemplate = $_GET['template'] ?? '';
if (!is_string($previewTemplate) || $previewTemplate === '') {
    $previewTemplate = '';
} elseif (!canPreviewTemplate($cooperative)) {
    $previewTemplate = '';
}

// Sesi ibu bapa (KOPSTORE) — dimulakan SELEPAS semakan sesi panel supaya token CSRF dalam <body data-csrf> milik store
CustomerAuth::start();

$templateCode = (new ResolveStoreTemplateJob($cooperative, $previewTemplate))->run();
$templateDir = APP_ROOT . '/templates/' . $templateCode;
$manifest = json_decode(file_get_contents($templateDir . '/manifest.json'), true);

$storePage = $_GET['page'] ?? 'home';
$pageFile = is_string($storePage) ? $templateDir . '/pages/' . $storePage . '.php' : '';
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

// Sesi panel dibaca hanya jika cookie wujud (tiada cookie sesi baharu untuk pelawat biasa)
function canPreviewTemplate($cooperative)
{
    if (!empty($_COOKIE[OwnerAuth::SESSION_NAME]) && OwnerAuth::user() !== null) {
        return true;
    }
    return $cooperative !== null && !empty($_COOKIE[VendorAuth::SESSION_NAME])
        && VendorAuth::vendorId() === (int) $cooperative['vendor_id'];
}
