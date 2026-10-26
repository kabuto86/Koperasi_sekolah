<?php
// Simpan draf / terbit pengumuman (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new SaveAnnouncementJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
