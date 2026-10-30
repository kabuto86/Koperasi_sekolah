<?php
// Tambah slot (satu setiap darjah) / sunting satu slot.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'school_opening');

$job = new SavePickupSlotJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::all());
Response::ok($job->run());
