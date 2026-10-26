<?php
// Batal PO (draf / dipesan tanpa barang diterima).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new CancelPurchaseOrderJob(VendorAuth::vendorId(), $cooperative['id'], VendorAuth::id(), Request::int('id'));
Response::ok($job->run());
