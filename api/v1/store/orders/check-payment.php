<?php
// Ibu bapa kembali dari gateway: pelayan menyemak sendiri status bil pending ke API gateway (parameter redirect diabaikan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('store_payment_check:' . CustomerAuth::id(), 30, 600);
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new CheckOrderPaymentJob(CustomerAuth::id(), $cooperative, Request::int('id'));
Response::ok($job->run());
