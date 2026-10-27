<?php
// Butiran pesanan ibu bapa (StoreOrderDetail). Pesanan orang lain / koperasi lain → 404.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new GetCustomerOrderJob(CustomerAuth::id(), $cooperative, Request::int('id'));
Response::ok($job->run());
