<?php
// Tukar kuantiti baris troli.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('store_cart:' . CustomerAuth::id(), 120, 60);
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new UpdateCartItemJob(CustomerAuth::id(), $cooperative, Request::all());
Response::ok($job->run());
