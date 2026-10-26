<?php
// Padam akaun ibu bapa sesi (PDPA) → log keluar.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new DeleteCustomerAccountJob(CustomerAuth::id(), Request::string('password'));
$result = $job->run();
CustomerAuth::logout();
Response::ok($result);
