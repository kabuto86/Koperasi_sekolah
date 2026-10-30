<?php
// Slot ambil satu musim.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'school_opening');

$job = new ListPickupSlotsJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('season_id'));
Response::ok($job->run());
