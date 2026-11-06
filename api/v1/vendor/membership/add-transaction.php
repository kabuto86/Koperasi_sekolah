<?php
// Beli / keluarkan syer ahli (pengeluaran tidak melebihi baki).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
ModuleAccess::require(VendorAuth::vendorId(), $cooperative['id'], 'membership');

$job = new AddShareTransactionJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::all());
Response::ok($job->run());
