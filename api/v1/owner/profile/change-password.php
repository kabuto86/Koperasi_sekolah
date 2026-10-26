<?php
// Tukar kata laluan pengguna owner semasa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireLogin();
Csrf::verifyRequest();

$job = new ChangeOwnerPasswordJob(OwnerAuth::id(), Request::all());
Response::ok($job->run());
