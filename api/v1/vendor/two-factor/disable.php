<?php
// Matikan 2FA sendiri (kata laluan + kod 2FA).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);
Csrf::verifyRequest();

$job = new DisableTotpJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
