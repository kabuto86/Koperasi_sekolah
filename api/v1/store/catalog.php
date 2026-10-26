<?php
// Katalog produk koperasi (slug) — awam.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new StoreCatalogJob($cooperative, Request::all());
Response::ok($job->run());
