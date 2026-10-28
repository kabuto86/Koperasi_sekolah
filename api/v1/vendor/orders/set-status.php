<?php
// Tukar status satu pesanan (sediakan / sedia / batal dengan sebab).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new UpdateOrderStatusJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::int('id'), Request::string('status'),
    Request::string('note'));
Response::ok($job->run());
