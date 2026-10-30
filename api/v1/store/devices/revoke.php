<?php
// Log keluar satu peranti apps dari senarai (id token milik ibu bapa sesi / token — B15).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();

$job = new RevokeApiTokenJob(CustomerAuth::id(), Request::int('id'));
Response::ok($job->run());
