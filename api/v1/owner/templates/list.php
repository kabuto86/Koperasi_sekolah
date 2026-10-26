<?php
// Senarai template frontend.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('template.manage');

$job = new ListTemplatesJob();
Response::ok($job->run());
