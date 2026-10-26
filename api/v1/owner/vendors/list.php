<?php
// Senarai vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('vendor.view');

$job = new ListVendorsJob(Request::all());
Response::ok($job->run());
