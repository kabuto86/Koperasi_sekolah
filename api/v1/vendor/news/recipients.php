<?php
// Bilangan ibu bapa penerima e-mel berita.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'news');

$job = new CountNewsRecipientsJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
