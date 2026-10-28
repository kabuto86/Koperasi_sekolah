<?php
// Status bayaran QR jualan POS (dari payments yang dikemas kini webhook; semakan semula ke gateway berhad).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
PosAuth::requireCashier($cooperative);

$job = new PosPaymentStatusJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('order_id'));
Response::ok($job->run());
