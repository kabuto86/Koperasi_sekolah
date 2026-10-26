<?php
// Semua koperasi semua vendor — baca sahaja (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListAllCooperativesJob(Request::all());
Response::ok($job->run());
