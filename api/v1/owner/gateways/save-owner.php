<?php
// Simpan gateway owner untuk kutip yuran.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('gateway.manage');
Csrf::verifyRequest();

$job = new SaveOwnerGatewayJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
