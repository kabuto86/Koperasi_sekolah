<?php
// Kata laluan baharu ibu bapa dari pautan e-mel (token sekali guna, 1 jam).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('reset_ip:' . Request::ip(), 20, 3600);

$job = new ResetPasswordJob('customer', Request::all());
Response::ok($job->run());
