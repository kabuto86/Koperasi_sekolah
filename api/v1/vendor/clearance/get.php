<?php
// Butiran jualan murah + item.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'clearance');

$job = new GetClearanceSaleJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('id'));
Response::ok($job->run());
