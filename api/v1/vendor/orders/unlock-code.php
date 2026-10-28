<?php
// Buka kunci kod 4 digit selepas 5 cubaan salah (pentadbir / pengurus).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new UnlockPickupCodeJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
