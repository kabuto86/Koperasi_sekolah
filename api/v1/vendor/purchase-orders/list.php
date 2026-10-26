<?php
// Senarai pesanan belian koperasi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListPurchaseOrdersJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
