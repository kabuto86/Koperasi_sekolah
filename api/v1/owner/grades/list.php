<?php
// Senarai darjah / tingkatan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('grade.manage');

$job = new ListGradesJob();
Response::ok($job->run());
