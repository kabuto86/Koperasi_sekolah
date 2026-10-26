<?php
// Padam pengumuman.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('announcement.manage');
Csrf::verifyRequest();

$job = new DeleteAnnouncementJob(OwnerAuth::id(), Request::int('id', 0));
Response::ok($job->run());
