<?php
// Kebenaran seorang kakitangan (lalai peranan ± pengecualian — PELAN 4.1).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requirePermission('staff.manage');

$job = new GetStaffPermissionsJob('vendor', VendorAuth::id(), VendorAuth::vendorId(), Request::int('id', 0));
Response::ok($job->run());
