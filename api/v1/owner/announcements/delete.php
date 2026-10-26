<?php
// Padam pengumuman (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new DeleteAnnouncementJob(OwnerAuth::id(), Request::int('id', 0));
Response::ok($job->run());
