<?php
// Anak saya — semua anak ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();

$job = new ListChildrenJob(CustomerAuth::id());
Response::ok($job->run());
