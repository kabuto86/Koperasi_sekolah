<?php
// Sunting darjah / tingkatan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('grade.manage');
Csrf::verifyRequest();

$job = new SaveGradeJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
