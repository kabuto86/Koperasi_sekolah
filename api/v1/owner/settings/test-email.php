<?php
// Hantar e-mel ujian ke email_queue (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SendTestEmailJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
