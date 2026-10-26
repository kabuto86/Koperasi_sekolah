<?php
// Daftar akaun ibu bapa → log masuk terus; e-mel pengesahan melalui email_queue.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('store_register_ip:' . Request::ip(), 10, 3600);

$job = new CustomerRegisterJob(Request::all());
$customer = $job->run();
CustomerAuth::login($customer['id'], false);
Response::ok(['customer' => LoadCustomerSessionJob::publicShape($customer)]);
