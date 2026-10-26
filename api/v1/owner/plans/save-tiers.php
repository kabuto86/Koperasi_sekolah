<?php
// Simpan peringkat harga koperasi tambahan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('plan.manage');
Csrf::verifyRequest();

$job = new SavePlanTiersJob(OwnerAuth::id(), Request::int('plan_id', 0), Request::arrayValue('tiers'));
Response::ok($job->run());
