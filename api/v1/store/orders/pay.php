<?php
// Bayar semula pesanan online yang belum dibayar → bil gateway (StartOnlinePaymentJob) → { order_id, payment }.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('store_payment_start:' . CustomerAuth::id(), 20, 600);
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new PayCustomerOrderJob(CustomerAuth::id(), $cooperative, Request::int('id'));
Response::ok($job->run());
