<?php
// Driver gateway & gateway owner (owner sahaja). Rahsia tidak dipulangkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireRole(['owner']);

$job = new ListPaymentGatewaysJob();
Response::ok($job->run());
