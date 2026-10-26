<?php
// Senarai pengumuman vendor (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListAnnouncementsJob(Request::all());
Response::ok($job->run());
