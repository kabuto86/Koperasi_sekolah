<?php
// Papan pemuka owner (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new GetOwnerDashboardJob();
Response::ok($job->run());
