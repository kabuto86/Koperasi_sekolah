<?php
// Pelan aktif untuk modal tukar pelan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);

$job = new ListVendorPlansJob(VendorAuth::vendorId());
Response::ok($job->run());
