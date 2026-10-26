<?php
// Benarkan / sekat driver gateway untuk vendor (owner sahaja).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireRole(['owner']);
Csrf::verifyRequest();

$job = new TogglePaymentGatewayJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
