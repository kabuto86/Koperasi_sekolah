<?php
// Senarai ambil ikut darjah & kelas + jumlah setiap produk.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new PickingListJob(VendorAuth::vendorId(), $cooperative, Request::all());
Response::ok($job->run());
