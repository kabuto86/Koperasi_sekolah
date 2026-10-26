<?php
// Buang anak ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new RemoveChildJob(CustomerAuth::id(), Request::int('id', 0));
Response::ok($job->run());
