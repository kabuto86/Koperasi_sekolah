<?php
// Senarai terjemahan: kunci × 4 bahasa, kunci hilang (owner sahaja — B14).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListTranslationsJob(Request::all());
Response::ok($job->run());
