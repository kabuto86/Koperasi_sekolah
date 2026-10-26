<?php
// Simpan kiraan stock take.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SaveStockTakeCountsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
