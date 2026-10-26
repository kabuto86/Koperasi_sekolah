<?php
// Kebenaran seorang kakitangan (lalai peranan ± pengecualian — PELAN 4.1).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('staff.manage');

$job = new GetStaffPermissionsJob('owner', OwnerAuth::id(), null, Request::int('id', 0));
Response::ok($job->run());
