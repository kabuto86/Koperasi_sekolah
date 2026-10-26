<?php
// Tetapan platform (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new GetPlatformSettingsJob();
Response::ok($job->run());
