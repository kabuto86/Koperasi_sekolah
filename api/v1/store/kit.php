<?php
// Kit sekolah untuk anak ibu bapa sesi di koperasi slug.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();
$cooperative = StoreSchool::require(Request::string('slug'));

$job = new StoreKitForChildJob(CustomerAuth::id(), $cooperative, Request::int('child_id', 0));
Response::ok($job->run());
