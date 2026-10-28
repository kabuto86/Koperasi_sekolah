<?php
// Tetapan koperasi. Bukan pentadbir (POS & skrin serah) → tanpa e-mel amaran.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetCooperativeSettingsJob($cooperative, VendorAuth::role() === 'vendor_admin');
Response::ok($job->run());
