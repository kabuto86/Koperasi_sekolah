<?php
// Terbitkan draf invois (draft → issued, e-mel kepada vendor) — owner sahaja.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new IssueInvoiceJob(OwnerAuth::id(), Request::int('id'));
Response::ok($job->run());
