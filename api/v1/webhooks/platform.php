<?php
// Panggilan balik gateway owner untuk bayaran invois langganan (?gateway={kod}). Tandatangan + semakan semula status ke API
// gateway + amaun — HandlePlatformCallbackJob. Tiada sesi / CSRF (dipanggil oleh pelayan gateway). Setiap panggilan dilog.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('POST');
RateLimit::enforce('webhook:' . Request::ip(), 300, 60);

$job = new HandlePlatformCallbackJob(Request::queryCode('gateway'), Request::webhook());
$result = $job->run();
Response::text($result['body'], $result['status']);
