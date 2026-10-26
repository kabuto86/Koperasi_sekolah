<?php
// Berita koperasi (modul news) — awam.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new ListStoreNewsJob($cooperative, Request::all());
Response::ok($job->run());
