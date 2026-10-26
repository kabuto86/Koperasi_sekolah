<?php
// Aktif / nyahaktif kakitangan owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('staff.manage');
Csrf::verifyRequest();

$job = new SetOwnerStaffActiveJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
