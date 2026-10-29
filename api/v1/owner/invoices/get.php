<?php
// Butiran invois (baris, bayaran, maklumat vendor) — owner & owner_staff.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new GetInvoiceJob(null, Request::int('id'));
Response::ok($job->run());
