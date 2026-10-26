<?php
// Senarai koperasi vendor sesi (pengurus/juruwang: hanya koperasi yang diberi). Simpan/sunting → B4.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);

$user = VendorAuth::user();
$allowedIds = $user['role'] === 'vendor_admin' ? null : $user['cooperative_ids'];
$job = new ListCooperativesJob(VendorAuth::vendorId(), $allowedIds, Request::all());
Response::ok($job->run());
