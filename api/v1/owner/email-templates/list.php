<?php
// Ringkasan templat e-mel.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('email_template.manage');

$job = new ListEmailTemplatesJob();
Response::ok($job->run());
