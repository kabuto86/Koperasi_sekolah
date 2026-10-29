<?php
// Henti modul pada hujung tempoh (undo = teruskan). Tiada refund.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new CancelModuleJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::string('code'), Request::bool('undo'));
Response::ok($job->run());
