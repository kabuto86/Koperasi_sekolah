<?php
// Tambah / sunting anak ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('store_child_save:' . CustomerAuth::id(), 60, 600);

$job = new SaveChildJob(CustomerAuth::id(), Request::all());
Response::ok($job->run());
