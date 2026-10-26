<?php
// Senarai pengumuman vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('announcement.manage');

$job = new ListAnnouncementsJob(Request::all());
Response::ok($job->run());
