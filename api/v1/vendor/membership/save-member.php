<?php
// Tambah / sunting ahli (no. ahli dijana pelayan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'membership');

$job = new SaveMemberJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::all());
Response::ok($job->run());
