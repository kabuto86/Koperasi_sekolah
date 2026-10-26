<?php
// Aktif / nyahaktif produk.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SetProductActiveJob(VendorAuth::vendorId(), $cooperative['id'], Request::int('id'), Request::bool('is_active'));
Response::ok($job->run());
