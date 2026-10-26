<?php
// Butiran vendor. — baca sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('vendor.view');

$job = new GetVendorJob(Request::int('id', 0));
Response::ok($job->run());
