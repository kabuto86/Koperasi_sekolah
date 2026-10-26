<?php
// Hantar e-mel ujian ke email_queue.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('settings.manage');
Csrf::verifyRequest();

$job = new SendTestEmailJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
