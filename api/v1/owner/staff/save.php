<?php
// Tambah / sunting kakitangan owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('staff.manage');
Csrf::verifyRequest();

$job = new SaveOwnerStaffJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
