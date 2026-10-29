<?php
// Kedai modul add-on & template untuk koperasi semasa (harga ikut kitaran). Dibenarkan semasa digantung (lihat sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetAddonCatalogJob(VendorAuth::vendorId(), $cooperative);
Response::ok($job->run());
