<?php
// Satu templat e-mel.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('email_template.manage');

$job = new GetEmailTemplateJob(Request::string('template_key'), Request::string('language_code'));
Response::ok($job->run());
