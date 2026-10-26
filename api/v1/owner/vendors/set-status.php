<?php
// Lulus / aktifkan semula / gantung vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('vendor.status');
Csrf::verifyRequest();

$job = new SetVendorStatusJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
