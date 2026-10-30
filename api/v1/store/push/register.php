<?php
// Apps Flutter (B15): daftar token FCM peranti ini (satu peranti push setiap token apps).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
ApiTokenAuth::requireToken();
RateLimit::enforce('store_push_register:' . ApiTokenAuth::id(), 20, 3600);

$job = new RegisterPushDeviceJob(CustomerAuth::id(), ApiTokenAuth::id(), Request::all());
Response::ok($job->run());
