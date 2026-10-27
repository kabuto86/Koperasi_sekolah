<?php
// Buat pesanan dari troli — semua dalam satu transaksi DB (CheckoutJob). Harga dikira semula di pelayan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('store_checkout:' . CustomerAuth::id(), 20, 600);
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new CheckoutJob(CustomerAuth::id(), $cooperative, Request::all());
Response::ok($job->run());
