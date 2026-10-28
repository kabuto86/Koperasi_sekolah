<?php
// Senarai promosi / kupon koperasi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListPromotionsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
