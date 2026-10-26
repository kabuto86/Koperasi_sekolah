<?php
// Lupa kata laluan kakitangan vendor — jawapan sentiasa sama.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('forgot_ip:' . Request::ip(), 10, 3600);

$job = new ForgotPasswordJob('vendor', Request::string('email'));
Response::ok($job->run());
