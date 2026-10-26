<?php
// Senarai template frontend (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListTemplatesJob();
Response::ok($job->run());
