<?php
// Senarai stok koperasi + bilangan stok rendah.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListInventoryJob(VendorAuth::vendorId(), $cooperative['id'], Request::all());
Response::ok($job->run());
