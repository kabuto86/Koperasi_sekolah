<?php
// Log audit platform.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('audit.view');

$job = new ListAuditLogsJob(Request::all());
Response::ok($job->run());
