<?php
// Sunting darjah / tingkatan (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveGradeJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
