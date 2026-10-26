<?php
// Tambah / sunting modul add-on.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('module.manage');
Csrf::verifyRequest();

$job = new SaveModuleJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
