<?php
// Padam slot ambil (ditolak jika bertempah).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'school_opening');

$job = new DeletePickupSlotJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
