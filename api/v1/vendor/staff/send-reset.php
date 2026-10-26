<?php
// Hantar pautan tetapkan kata laluan kepada kakitangan vendor sesi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();

$job = new SendStaffResetJob(VendorAuth::vendorId(), VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
