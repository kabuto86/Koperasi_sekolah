<?php
// Log audit platform (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListAuditLogsJob(Request::all());
Response::ok($job->run());
