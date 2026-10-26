<?php
// Simpan kebenaran seorang kakitangan: permissions = kod yang dibenarkan, atau reset = true (PELAN 4.1).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('staff.manage');
Csrf::verifyRequest();

$job = new SaveStaffPermissionsJob('owner', OwnerAuth::id(), null, Request::all());
Response::ok($job->run());
