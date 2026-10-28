<?php
// Batal bayaran QR yang belum dibayar — tahanan stok dilepas.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
$cashier = PosAuth::requireCashier($cooperative);

$job = new CancelPosPaymentJob(VendorAuth::vendorId(), $cooperative, $cashier, Request::int('order_id'));
Response::ok($job->run());
