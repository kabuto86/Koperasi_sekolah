<?php
// Apps Flutter (B15): log masuk e-mel / no. telefon + kata laluan → token Bearer (dipulangkan sekali sahaja).
// Tiada sesi / cookie, jadi tiada CSRF. Had kadar IP + kunci akaun 5 gagal (CustomerLoginJob — sama seperti web).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
RateLimit::enforce('store_token_login_ip:' . Request::ip(), 30, 900);

$customer = (new CustomerLoginJob(Request::all()))->run();
$job = new IssueApiTokenJob($customer['id'], Request::string('device_name'));
Response::ok($job->run() + ['customer' => LoadCustomerSessionJob::publicShape($customer)]);
