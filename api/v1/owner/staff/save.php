<?php
// Tambah / sunting kakitangan owner (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveOwnerStaffJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
