<?php
// Driver gateway & gateway owner. Rahsia tidak dipulangkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requirePermission('gateway.manage');

$job = new ListPaymentGatewaysJob();
Response::ok($job->run());
