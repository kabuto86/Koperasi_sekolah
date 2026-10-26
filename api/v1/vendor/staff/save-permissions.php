<?php
// Simpan kebenaran seorang kakitangan: permissions = kod yang dibenarkan, atau reset = true (PELAN 4.1).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requirePermission('staff.manage');
Csrf::verifyRequest();

$job = new SaveStaffPermissionsJob('vendor', VendorAuth::id(), VendorAuth::vendorId(), Request::all());
Response::ok($job->run());
