<?php
// Tambah / sunting pelan (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SavePlanJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
