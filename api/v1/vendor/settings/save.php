<?php
// Simpan tetapan koperasi (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new SaveCooperativeSettingsJob($cooperative, VendorAuth::id(), Request::all());
Response::ok($job->run());
