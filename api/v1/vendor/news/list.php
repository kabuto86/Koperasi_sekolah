<?php
// Senarai berita koperasi (modul news).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'news');

$job = new ListNewsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
