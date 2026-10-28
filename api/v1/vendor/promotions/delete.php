<?php
// Padam promosi yang belum pernah digunakan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new DeletePromotionJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
