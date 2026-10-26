<?php
// Senarai vendor (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListVendorsJob(Request::all());
Response::ok($job->run());
