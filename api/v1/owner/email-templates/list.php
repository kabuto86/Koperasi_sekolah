<?php
// Ringkasan templat e-mel (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListEmailTemplatesJob();
Response::ok($job->run());
