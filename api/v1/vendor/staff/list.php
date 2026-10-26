<?php
// Senarai kakitangan vendor sesi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requirePermission('staff.manage');

$job = new ListVendorStaffJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
