<?php
// Sunting template frontend (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveTemplateJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
