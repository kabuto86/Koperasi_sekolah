<?php
// Simpan tetapan platform.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('settings.manage');
Csrf::verifyRequest();

$job = new SavePlatformSettingsJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
