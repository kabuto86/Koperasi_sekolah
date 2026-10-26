<?php
// Tetapan platform.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('settings.manage');

$job = new GetPlatformSettingsJob();
Response::ok($job->run());
