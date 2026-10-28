<?php
// Terima bayaran tunai di kaunter (rekod payments, juruwang).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ReceiveCashPaymentJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::int('id'), Request::get('amount_received'));
Response::ok($job->run());
