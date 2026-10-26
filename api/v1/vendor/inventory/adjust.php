<?php
// Pelarasan stok dengan sebab (pergerakan adjust) + stok minimum.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new AdjustStockJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::all());
Response::ok($job->run());
