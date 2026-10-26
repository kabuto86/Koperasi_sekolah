<?php
// Troli ibu bapa sesi di koperasi slug (CartQuote dikira semula).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new GetCartJob(CustomerAuth::id(), $cooperative);
Response::ok($job->run());
