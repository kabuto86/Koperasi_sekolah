<?php
// Senarai jualan murah koperasi (modul clearance).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'clearance');

$job = new ListClearanceSalesJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
