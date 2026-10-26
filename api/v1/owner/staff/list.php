<?php
// Senarai kakitangan owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('staff.manage');

$job = new ListOwnerStaffJob();
Response::ok($job->run());
