<?php
// Senarai kakitangan owner (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListOwnerStaffJob();
Response::ok($job->run());
