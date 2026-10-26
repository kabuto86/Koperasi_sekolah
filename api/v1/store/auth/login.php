<?php
// Log masuk ibu bapa (e-mel atau no. telefon). "Ingat saya" → cookie 30 hari.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('store_login_ip:' . Request::ip(), 30, 900);

$job = new CustomerLoginJob(Request::all());
$customer = $job->run();
CustomerAuth::login($customer['id'], Request::bool('remember'));
Response::ok(['customer' => LoadCustomerSessionJob::publicShape($customer)]);
