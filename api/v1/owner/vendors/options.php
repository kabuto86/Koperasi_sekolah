<?php
// Pilihan vendor ringkas untuk dropdown (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListVendorOptionsJob();
Response::ok($job->run());
