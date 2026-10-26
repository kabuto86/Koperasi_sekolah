<?php
// Naik darjah anak ibu bapa sesi (musim pre-order).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new PromoteChildJob(CustomerAuth::id(), Request::all());
Response::ok($job->run());
