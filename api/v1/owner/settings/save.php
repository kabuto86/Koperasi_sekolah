<?php
// Simpan tetapan platform (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SavePlatformSettingsJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
