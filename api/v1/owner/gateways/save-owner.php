<?php
// Simpan gateway owner untuk kutip yuran (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveOwnerGatewayJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
