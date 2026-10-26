<?php
// Tetapkan kata laluan kakitangan vendor dari pautan e-mel (reset atau jemputan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('reset_ip:' . Request::ip(), 20, 3600);

$job = new ResetPasswordJob('vendor', Request::all());
Response::ok($job->run());
