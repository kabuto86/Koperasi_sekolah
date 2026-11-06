<?php
// Simpan profil kakitangan vendor semasa (nama, e-mel, telefon, bahasa).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);
Csrf::verifyRequest();

$job = new SaveVendorProfileJob(VendorAuth::vendorId(), VendorAuth::id(), Request::all());
Response::ok($job->run());
