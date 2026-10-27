<?php
// Ringkasan checkout: troli + promosi/kupon, cukai, kaedah bayaran, kod 4 digit, slot ambil (PELAN 18.12).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new GetCheckoutQuoteJob(CustomerAuth::id(), $cooperative, Request::string('coupon_code'));
Response::ok($job->run());
