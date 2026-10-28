<?php
// Jana laporan koperasi (type: daily, monthly, payment, product, profit, stock, grade, class). CSV dijana di pelayar.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new RunReportJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
