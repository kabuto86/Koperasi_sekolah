<?php
// Pilihan pelan tanpa harga untuk tapisan vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission(['vendor.view', 'plan.manage']);

$job = new ListPlanOptionsJob();
Response::ok($job->run());
