<?php
// Rekod bayaran koperasi (online & tunai) + ringkasan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListPaymentRecordsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
