<?php
// Sahkan e-mel ibu bapa dari pautan e-mel (token sekali guna).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('verify_ip:' . Request::ip(), 20, 3600);

$job = new VerifyEmailJob(Request::string('token'));
Response::ok($job->run());
