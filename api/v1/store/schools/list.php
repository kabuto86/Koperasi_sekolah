<?php
// Senarai sekolah (koperasi aktif) untuk halaman "Pilih sekolah" — awam.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');

$job = new SearchSchoolsJob(Request::all());
Response::ok($job->run());
