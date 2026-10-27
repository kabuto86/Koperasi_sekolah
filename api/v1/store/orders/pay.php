<?php
// Bayar semula pesanan online yang belum dibayar (gateway — B8).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new PayCustomerOrderJob(CustomerAuth::id(), $cooperative, Request::int('id'));
Response::ok($job->run());
