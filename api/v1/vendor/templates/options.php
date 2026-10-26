<?php
// Pilihan template untuk borang koperasi (subscribed = percuma / dilanggan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);

$job = new ListTemplateOptionsJob(VendorAuth::vendorId());
Response::ok($job->run());
