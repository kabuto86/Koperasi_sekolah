<?php
// Apps Flutter (B15): log keluar — batal token semasa + buang peranti push yang didaftar olehnya.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::requireLogin();
Csrf::verifyRequest();
ApiTokenAuth::requireToken();

$job = new RevokeApiTokenJob(CustomerAuth::id(), ApiTokenAuth::id());
Response::ok($job->run());
