<?php
// Batal pertukaran pelan yang dijadualkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);
Csrf::verifyRequest();

$job = new CancelPlanChangeJob(VendorAuth::vendorId(), VendorAuth::id());
Response::ok($job->run());
