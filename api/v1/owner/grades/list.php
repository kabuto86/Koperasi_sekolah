<?php
// Senarai darjah / tingkatan (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListGradesJob();
Response::ok($job->run());
