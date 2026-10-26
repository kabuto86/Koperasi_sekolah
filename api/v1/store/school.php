<?php
// Maklumat sekolah untuk layout store (slug → koperasi di pelayan) — awam.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new GetStoreSchoolJob($cooperative);
Response::ok($job->run());
