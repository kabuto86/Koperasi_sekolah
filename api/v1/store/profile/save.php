<?php
// Simpan profil ibu bapa sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new SaveCustomerProfileJob(CustomerAuth::id(), Request::all());
Response::ok(LoadCustomerSessionJob::publicShape($job->run()));
