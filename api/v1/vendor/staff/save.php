<?php
// Tambah / sunting kakitangan vendor sesi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();

$job = new SaveVendorStaffJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
