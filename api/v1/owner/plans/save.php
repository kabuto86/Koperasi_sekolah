<?php
// Tambah / sunting pelan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('plan.manage');
Csrf::verifyRequest();

$job = new SavePlanJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
