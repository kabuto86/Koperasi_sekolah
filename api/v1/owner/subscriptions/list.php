<?php
// Senarai langganan semua vendor (owner & owner_staff — baca sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListSubscriptionsJob(Request::all());
Response::ok($job->run());
