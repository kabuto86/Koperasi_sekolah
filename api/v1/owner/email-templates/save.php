<?php
// Simpan templat e-mel (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveEmailTemplateJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
