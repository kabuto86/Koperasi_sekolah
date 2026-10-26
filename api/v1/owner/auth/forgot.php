<?php
// Lupa kata laluan owner — jawapan sentiasa sama.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('forgot_ip:' . Request::ip(), 10, 3600);

$job = new ForgotPasswordJob('owner', Request::string('email'));
Response::ok($job->run());
