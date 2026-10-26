<?php
// Satu templat e-mel (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new GetEmailTemplateJob(Request::string('template_key'), Request::string('language_code'));
Response::ok($job->run());
