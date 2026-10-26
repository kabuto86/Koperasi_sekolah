<?php
// Pilihan pelan tanpa harga untuk tapisan vendor (owner & owner_staff).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner', 'owner_staff']);

$job = new ListPlanOptionsJob();
Response::ok($job->run());
