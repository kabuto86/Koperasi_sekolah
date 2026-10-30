<?php
// Tambah / sunting berita (+ e-mel kepada ibu bapa sekali sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'news');

$job = new SaveNewsJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::all());
Response::ok($job->run());
