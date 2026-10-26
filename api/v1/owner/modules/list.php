<?php
// Katalog modul add-on.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('module.manage');

$job = new ListModulesJob();
Response::ok($job->run());
