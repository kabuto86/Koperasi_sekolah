<?php
// Terima bayaran tunai di kaunter (rekod payments, juruwang). B10: received_by = juruwang PIN POS jika ada (tunai laci syif).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ReceiveCashPaymentJob(VendorAuth::vendorId(), $cooperative, PosAuth::counterUserId($cooperative), Request::int('id'), Request::get('amount_received'));
Response::ok($job->run());
