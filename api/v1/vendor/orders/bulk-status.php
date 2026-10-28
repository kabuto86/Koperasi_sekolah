<?php
// Tukar status beberapa pesanan (tiada batal pukal).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new BulkOrderStatusJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::arrayValue('ids'), Request::string('status'));
Response::ok($job->run());
