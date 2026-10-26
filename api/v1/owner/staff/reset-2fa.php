<?php
// Set semula 2FA kakitangan owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('staff.manage');
Csrf::verifyRequest();

$job = new ResetOwnerStaff2faJob(OwnerAuth::id(), Request::int('id'));
Response::ok($job->run());
