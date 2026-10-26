<?php
// Buang satu baris troli.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new RemoveCartItemJob(CustomerAuth::id(), $cooperative, Request::int('id', 0));
Response::ok($job->run());
