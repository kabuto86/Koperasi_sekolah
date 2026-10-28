<?php
// Cari pesanan di skrin serah (QR, no. pendek, nama / kelas) ikut cara yang aktif.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new FindOrdersForPickupJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
