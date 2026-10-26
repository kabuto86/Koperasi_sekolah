<?php
// Katalog modul add-on (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListModulesJob();
Response::ok($job->run());
