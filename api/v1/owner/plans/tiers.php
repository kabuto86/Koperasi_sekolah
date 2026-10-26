<?php
// Peringkat harga koperasi tambahan satu pelan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('plan.manage');

$job = new GetPlanTiersJob(Request::int('plan_id', 0));
Response::ok($job->run());
