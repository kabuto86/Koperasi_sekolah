<?php
// Nilai tersimpan satu driver gateway untuk koperasi (tanpa rahsia).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetCooperativeGatewayJob(VendorAuth::vendorId(), $cooperative['id'], Request::string('gateway_code'));
Response::ok($job->run());
