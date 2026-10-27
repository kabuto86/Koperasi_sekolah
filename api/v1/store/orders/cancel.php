<?php
// Batal pesanan yang belum dibayar / tunai (tahanan stok dilepaskan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new CancelOrderJob(CustomerAuth::id(), $cooperative, Request::int('id'));
Response::ok($job->run());
