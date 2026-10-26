<?php
// Mula stock take (satu terbuka setiap koperasi).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new StartStockTakeJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id());
Response::ok($job->run());
