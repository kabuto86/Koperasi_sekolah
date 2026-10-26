<?php
// Jualan murah yang sedang berjalan (modul clearance) — awam.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new ListStoreClearanceJob($cooperative);
Response::ok($job->run());
