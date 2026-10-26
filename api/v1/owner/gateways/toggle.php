<?php
// Benarkan / sekat driver gateway untuk vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requirePermission('gateway.manage');
Csrf::verifyRequest();

$job = new TogglePaymentGatewayJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
