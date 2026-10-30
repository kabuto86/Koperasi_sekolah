<?php
// Peranti apps yang log masuk ke akaun ibu bapa (sesi web atau token apps — B15).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
CustomerAuth::requireLogin();

$job = new ListApiTokensJob(CustomerAuth::id(), ApiTokenAuth::id());
Response::ok($job->run());
