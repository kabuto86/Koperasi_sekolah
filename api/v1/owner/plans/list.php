<?php
// Senarai pelan dengan harga.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('plan.manage');

$job = new ListPlansJob();
Response::ok($job->run());
