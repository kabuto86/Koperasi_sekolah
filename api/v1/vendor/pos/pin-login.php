<?php
// Log masuk juruwang dengan PIN (5 salah → dikunci sehingga pentadbir tetapkan PIN baharu).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));
RateLimit::enforce('pos_pin:' . VendorAuth::id(), 30, 600);

$job = new PosLoginJob(VendorAuth::vendorId(), $cooperative, Request::int('user_id'), Request::string('pin'));
$result = $job->run();
PosAuth::setCashier($cooperative['id'], $result['cashier']['id']);
Response::ok($result);
