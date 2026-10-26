<?php
// Senarai pelan dengan harga (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListPlansJob();
Response::ok($job->run());
