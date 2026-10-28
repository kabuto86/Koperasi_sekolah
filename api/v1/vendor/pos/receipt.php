<?php
// Resit jualan POS (salinan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
PosAuth::requireCashier($cooperative);

$job = new GetPosReceiptJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('order_id'));
Response::ok($job->run());
