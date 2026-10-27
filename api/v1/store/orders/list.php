<?php
// "Pesanan saya" di sekolah slug — tapis anak & tahun (PELAN 18.12).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new ListCustomerOrdersJob(CustomerAuth::id(), $cooperative, Request::all());
Response::ok($job->run());
