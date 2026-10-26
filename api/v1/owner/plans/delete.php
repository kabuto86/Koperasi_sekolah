<?php
// Padam pelan yang tiada langganan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('plan.manage');
Csrf::verifyRequest();

$job = new DeletePlanJob(OwnerAuth::id(), Request::int('id', 0));
Response::ok($job->run());
