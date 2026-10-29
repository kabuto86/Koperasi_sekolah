<?php
// Butiran invois vendor sesi (milik vendor lain → 404).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES, true);

$job = new GetInvoiceJob(VendorAuth::vendorId(), Request::int('id'));
Response::ok($job->run());
