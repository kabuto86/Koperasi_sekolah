<?php
// Tambah / sunting modul add-on (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveModuleJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
