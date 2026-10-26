<?php
// Senarai kakitangan vendor sesi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);

$job = new ListVendorStaffJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
