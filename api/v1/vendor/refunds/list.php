<?php
// Senarai refund koperasi (tapis q, method, status, date_from, date_to) + jumlah refund selesai.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListRefundsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
