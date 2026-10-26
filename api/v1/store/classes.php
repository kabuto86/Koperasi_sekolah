<?php
// Cadangan nama kelas ikut darjah (borang anak) — awam.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new ListStoreClassesJob($cooperative, Request::string('grade'));
Response::ok($job->run());
