<?php
// Sunting template frontend.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('template.manage');
Csrf::verifyRequest();

$job = new SaveTemplateJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
