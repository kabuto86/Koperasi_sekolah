<?php
// Simpan draf / terbit pengumuman.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('announcement.manage');
Csrf::verifyRequest();

$job = new SaveAnnouncementJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
