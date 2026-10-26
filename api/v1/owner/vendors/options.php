<?php
// Pilihan vendor ringkas untuk dropdown.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission(['vendor.view', 'cooperative.view', 'announcement.manage']);

$job = new ListVendorOptionsJob();
Response::ok($job->run());
