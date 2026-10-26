<?php
// Papan pemuka owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('dashboard.view');

$job = new GetOwnerDashboardJob();
Response::ok($job->run());
