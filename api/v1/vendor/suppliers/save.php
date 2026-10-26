<?php
// Tambah / sunting pembekal vendor sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
Csrf::verifyRequest();

$job = new SaveSupplierJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
