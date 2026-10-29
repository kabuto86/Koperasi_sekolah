<?php
// Invois vendor sesi (tanpa draf). Dibenarkan semasa digantung.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);

$job = new ListInvoicesJob(VendorAuth::vendorId(), Request::all());
Response::ok($job->run());
