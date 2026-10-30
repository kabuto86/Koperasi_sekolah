<?php
// Padam musim pre-order (ditolak jika ada tempahan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'school_opening');

$job = new DeletePreorderSeasonJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
