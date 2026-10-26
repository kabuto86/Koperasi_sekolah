<?php
// Semua koperasi semua vendor — baca sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('cooperative.view');

$job = new ListAllCooperativesJob(Request::all());
Response::ok($job->run());
