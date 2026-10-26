<?php
// Lulus / aktifkan semula / gantung vendor (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SetVendorStatusJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
