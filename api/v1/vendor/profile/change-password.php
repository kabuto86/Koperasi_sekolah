<?php
// Tukar kata laluan kakitangan vendor semasa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);
Csrf::verifyRequest();

$job = new ChangeVendorPasswordJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
