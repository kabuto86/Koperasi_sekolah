<?php
// Katalog POS: produk aktif, varian, barcode, stok boleh dijual & promosi berjalan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new PosCatalogJob($cooperative);
Response::ok($job->run());
