<?php
// Tukar kata laluan ibu bapa sesi / apps (token apps lain dibatal — B15).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new ChangeCustomerPasswordJob(CustomerAuth::id(), Request::all(), ApiTokenAuth::id());
Response::ok($job->run());
