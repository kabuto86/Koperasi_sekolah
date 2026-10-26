<?php
// Simpan templat e-mel.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('email_template.manage');
Csrf::verifyRequest();

$job = new SaveEmailTemplateJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
