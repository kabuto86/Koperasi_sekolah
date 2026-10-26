<?php
// Lupa kata laluan ibu bapa — jawapan sentiasa { sent: true }.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('forgot_ip:' . Request::ip(), 10, 3600);

$job = new ForgotPasswordJob('customer', Request::string('email'));
Response::ok($job->run());
