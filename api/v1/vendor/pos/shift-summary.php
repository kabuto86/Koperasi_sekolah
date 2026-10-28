<?php
// Ringkasan syif / laporan Z.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
PosAuth::requireCashier($cooperative);

$job = new ShiftReportJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('shift_id'));
Response::ok($job->run());
