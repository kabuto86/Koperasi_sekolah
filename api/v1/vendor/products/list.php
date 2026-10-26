<?php
// Senarai produk koperasi (tapis q, kategori, darjah, status, stok rendah).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListProductsJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
