<?php
// Uji kunci API gateway aktif koperasi (panggilan sebenar ke gateway).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();
RateLimit::enforce('vendor_gateway_test:' . VendorAuth::id(), 10, 600);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new TestCooperativeGatewayJob(VendorAuth::vendorId(), $cooperative['id']);
Response::ok($job->run());
