<?php
// Tetapkan kata laluan owner dari pautan e-mel (reset atau jemputan kakitangan).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('reset_ip:' . Request::ip(), 20, 3600);

$job = new ResetPasswordJob('owner', Request::all());
Response::ok($job->run());
