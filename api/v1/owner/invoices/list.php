<?php
// Senarai invois semua vendor (tapis q, status, vendor_id) — owner & owner_staff.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListInvoicesJob(null, Request::all());
Response::ok($job->run());
