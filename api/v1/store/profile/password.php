<?php
// Tukar kata laluan ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new ChangeCustomerPasswordJob(CustomerAuth::id(), Request::all());
Response::ok($job->run());
