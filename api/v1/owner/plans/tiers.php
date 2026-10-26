<?php
// Peringkat harga koperasi tambahan satu pelan (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new GetPlanTiersJob(Request::int('plan_id', 0));
Response::ok($job->run());
