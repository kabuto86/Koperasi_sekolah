<?php
// Panggilan balik ToyyibPay (?coop={id}). Tandatangan + semakan semula status ke API gateway + amaun — HandleGatewayCallbackJob.
// Tiada sesi / CSRF (dipanggil oleh pelayan gateway). Setiap panggilan dilog dalam webhook_logs.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('POST');
RateLimit::enforce('webhook:' . Request::ip(), 300, 60);

$job = new HandleGatewayCallbackJob('toyyibpay', Request::queryInt('coop'), Request::webhook());
$result = $job->run();
Response::text($result['body'], $result['status']);
