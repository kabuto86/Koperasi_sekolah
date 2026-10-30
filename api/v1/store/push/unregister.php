<?php
// Apps Flutter (B15): matikan push untuk peranti ini (token apps kekal log masuk).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
ApiTokenAuth::requireToken();

$job = new UnregisterPushDeviceJob(CustomerAuth::id(), ApiTokenAuth::id());
Response::ok($job->run());
