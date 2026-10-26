<?php
// Tambah / sunting produk (+ varian, darjah, terjemahan, gambar).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SaveProductJob(VendorAuth::vendorId(), $cooperative, VendorAuth::id(), Request::all());
Response::ok($job->run());
