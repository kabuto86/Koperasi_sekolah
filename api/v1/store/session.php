<?php
// Sesi store untuk header: ibu bapa (null jika belum log masuk — bukan 401), troli sekolah semasa, sekolah anak-anak.
require __DIR__ . '/../../../core/bootstrap.php';

Request::requireMethod('GET');

$job = new GetStoreSessionJob(CustomerAuth::customer(), Request::string('slug'));
Response::ok($job->run());
