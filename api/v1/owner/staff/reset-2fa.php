<?php
// Set semula 2FA kakitangan owner (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new ResetOwnerStaff2faJob(OwnerAuth::id(), Request::int('id'));
Response::ok($job->run());
