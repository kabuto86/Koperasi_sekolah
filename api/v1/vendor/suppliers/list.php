<?php
// Senarai pembekal vendor sesi (dikongsi semua koperasi).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);

$job = new ListSuppliersJob(VendorAuth::vendorId(), Request::all());
Response::ok($job->run());
