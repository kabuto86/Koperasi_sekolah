<?php
// Set semula 2FA kakitangan vendor sesi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();

$job = new ResetVendorStaff2faJob(VendorAuth::vendorId(), VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
