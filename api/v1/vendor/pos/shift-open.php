<?php
// Buka syif (tunai permulaan laci, sen).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
$cashier = PosAuth::requireCashier($cooperative);

$job = new OpenShiftJob(VendorAuth::vendorId(), $cooperative, $cashier, Request::get('opening_cash'));
Response::ok($job->run());
