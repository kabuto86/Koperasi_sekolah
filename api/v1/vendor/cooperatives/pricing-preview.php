<?php
// Anggaran harga koperasi seterusnya + had pelan (pentadbir sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ADMIN_ROLES);

$job = new GetCooperativePricingJob(VendorAuth::vendorId());
Response::ok($job->run());
