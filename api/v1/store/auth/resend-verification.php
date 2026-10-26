<?php
// Hantar semula pautan pengesahan e-mel kepada ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
RateLimit::enforce('resend_verify:' . CustomerAuth::id(), 5, 3600);

$job = new SendVerificationEmailJob(CustomerAuth::id());
$result = $job->run();
Response::ok($result['verified'] ? ['verified' => true] : ['email' => $result['email']]);
