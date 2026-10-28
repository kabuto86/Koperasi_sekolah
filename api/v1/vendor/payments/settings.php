<?php
// Tetapan bayaran koperasi: kaedah, driver dibenarkan, gateway aktif (tanpa rahsia), URL panggilan balik.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new GetPaymentSettingsJob(VendorAuth::vendorId(), $cooperative);
Response::ok($job->run());
