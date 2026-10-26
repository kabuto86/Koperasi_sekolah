<?php
// Aktif / nyahaktif kakitangan owner (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SetOwnerStaffActiveJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
