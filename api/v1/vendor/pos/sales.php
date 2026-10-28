<?php
// Jualan POS satu syif.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
PosAuth::requireCashier($cooperative);

$job = new ListShiftSalesJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
