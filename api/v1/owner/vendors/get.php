<?php
// Butiran vendor (owner & owner_staff) — baca sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new GetVendorJob(Request::int('id', 0));
Response::ok($job->run());
