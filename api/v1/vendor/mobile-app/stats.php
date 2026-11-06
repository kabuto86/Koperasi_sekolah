<?php
// Penggunaan apps ibu bapa koperasi semasa (modul mobile_app) — kiraan sahaja + pautan muat turun.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'mobile_app');

$job = new GetMobileAppStatsJob(VendorAuth::vendorId(), $cooperative);
Response::ok($job->run());
