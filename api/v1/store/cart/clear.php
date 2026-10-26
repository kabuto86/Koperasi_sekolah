<?php
// Kosongkan troli ibu bapa sesi di koperasi slug.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new ClearCartJob(CustomerAuth::id(), $cooperative);
Response::ok($job->run());
