<?php
// Butiran satu produk koperasi (slug) — awam.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new StoreProductJob($cooperative, Request::int('id', 0));
Response::ok($job->run());
